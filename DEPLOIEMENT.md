# Déployer DOCTORY

L'application est une application Next.js : elle a besoin d'un **processus Node
20.9 ou supérieur** et d'une base **PostgreSQL**. Un hébergement mutualisé PHP
(AwardSpace et équivalents) ne peut pas l'exécuter — ce n'est pas une question
de quota, un serveur PHP ne fait pas tourner Node.

Deux variables d'environnement suffisent :

| Variable | Rôle |
|---|---|
| `DATABASE_URL` | Chaîne de connexion PostgreSQL |
| `SESSION_SECRET` | Signe le cookie de session (HMAC-SHA256) |

Générer le secret :

```bash
node -e "console.log(require('crypto').randomBytes(48).toString('base64url'))"
```

---

## 1. La base : Neon (gratuit)

1. Créer un compte sur https://neon.com et un projet, région **Europe**
   (Francfort ou Paris — c'est le plus proche de l'Algérie).
2. Dans **Connect**, copier la chaîne **Pooled connection**, celle dont l'hôte
   contient `-pooler`.

   Le point de connexion mutualisé n'est pas un détail : un hébergement
   serverless démarre de nombreuses instances courtes, et chacune ouvrant son
   propre pool épuiserait la limite de connexions bien avant que le trafic ne le
   justifie.
3. Garder cette chaîne de côté, elle sert aux étapes 2 et 3.

Le plan gratuit de Neon n'expire pas. La base se met en veille après inactivité :
la première requête suivante prend quelques secondes.

## 2. Créer le schéma et les données de démonstration

Depuis votre machine, une seule fois :

```bash
export DATABASE_URL="postgresql://…-pooler…/neondb?sslmode=require"

npm install
npx prisma migrate deploy   # crée les 20 tables et les énumérations
npm run db:seed             # 58 wilayas, 1 535 communes, 373 partenaires
```

Le peuplement est déterministe : le relancer reproduit exactement la même
démonstration.

## 3. L'application : Vercel (gratuit)

1. Pousser le dépôt sur GitHub s'il ne l'est pas déjà.
2. Sur https://vercel.com : **Add New… → Project**, importer le dépôt.
   Le préréglage Next.js est détecté seul, ne rien changer.
3. Avant de valider, ouvrir **Environment Variables** et ajouter :
   - `DATABASE_URL` — la chaîne mutualisée de l'étape 1
   - `SESSION_SECRET` — la valeur générée plus haut
4. **Deploy**. Environ deux minutes.

### Attention à la licence

Le plan **Hobby de Vercel interdit l'usage commercial**, et sa définition inclut
explicitement « être payé pour créer ou héberger le site ». Un projet réalisé
pour un client entre dans ce cadre, même si le site ne vend rien.

- Prototype personnel, avant tout engagement : le plan Hobby convient.
- Projet client : plan **Pro** (20 $/mois), ou un hébergeur sans cette clause.

### Alternative gratuite sans cette clause

**Render** — service web gratuit, 750 heures par mois. Il se met en veille après
15 minutes sans trafic et le réveil prend environ une minute, ce qui est
acceptable pour une démonstration. Garder Neon pour la base : le PostgreSQL
gratuit de Render expire au bout de 30 jours.

## 4. Vérifier

- La page d'accueil affiche « 352 professionnels » et « 58 wilayas sur 58 ».
  Des zéros signifient que le peuplement n'a pas été exécuté sur cette base.
- Se connecter avec `admin@doctory.dz` / `doctory-demo`.
- Chercher « cardiologue » : 14 résultats.

---

## Vers un hébergement algérien

Pour la production, un hébergeur local (Octenium et équivalents) réduit la
latence, permet le paiement en dinars et garde les données en Algérie — ce qui
compte pour une plateforme de santé.

À vérifier auprès de l'hébergeur avant de souscrire :

1. **Node.js 20 minimum.** Next.js 16.3.3 l'exige.
2. **PostgreSQL**, ou à défaut MySQL — voir la réserve ci-dessous.
3. **Accès SSH**, pour lancer les migrations.

Sur un mutualisé cPanel, Node tourne derrière Passenger : il faut construire en
mode `standalone` (`output: "standalone"` dans `next.config.ts`) et désigner
`server.js` comme fichier de démarrage.

**Si l'hébergeur ne propose que MySQL** : ce schéma ne porte aucune annotation
`@db.`, donc sous MySQL chaque `String` devient `VARCHAR(191)`. Sept colonnes de
texte libre déborderaient — dont `Appointment.reason`, que le formulaire autorise
jusqu'à 400 caractères : elles seraient tronquées ou refusées. Il faut leur
ajouter `@db.Text` avant de migrer. PostgreSQL fait correspondre `String` à
`text`, ce qui est la raison pour laquelle rien d'autre n'a changé ici.
