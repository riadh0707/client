# Bella Rosa — Boutique e-commerce beauté premium

> Boutique e-commerce complète (cosmétique · maquillage · soins · parfums · onglerie · accessoires)
> conçue pour le marché algérien. **PHP Vanilla · MySQL 8 · HTML5 · CSS3 · JavaScript Vanilla.**
> Aucun framework. Optimisée pour l'hébergement **AwardSpace Free**.

![Bella Rosa](assets/images/og-image.svg)

---

## ✨ Aperçu

Bella Rosa est une démonstration **quasi-commerciale** : une véritable boutique
professionnelle (storefront + espace client + administration) prête à être
présentée à un client réel (institut de beauté, marque cosmétique, parfumerie…).

- 🛍️ **Storefront premium** : hero animé, slider, produits vedettes, nouveautés,
  meilleures ventes, promotions, vente flash avec compte à rebours, collections,
  marques partenaires, témoignages, blog, newsletter, contact + Google Maps.
- 🔎 **Recherche instantanée** avec suggestions, filtres (prix, catégorie, marque,
  promo, disponibilité) et tris multiples.
- 🛒 **Panier & commande** : ajout/suppression AJAX, codes promo, barre de
  livraison gratuite, estimation, paiement à la livraison (architecture prête
  pour BaridiMob, CCP, Edahabia, CIB, Visa, Mastercard, PayPal).
- 👤 **Comptes clients** : inscription, connexion, profil, historique de
  commandes, favoris, adresses, notifications, points de fidélité.
- ❤️ **Favoris & comparateur** de produits, produits récemment vus, aperçu rapide
  (quick view), zoom image, notifications toast, mode sombre.
- 🎛️ **Administration moderne** : tableau de bord (KPI, graphiques), CRUD produits,
  catégories, commandes (+ export CSV), clients, avis (modération), blog,
  promotions/coupons, paramètres du site.
- 🔐 **Sécurité** : PDO + requêtes préparées, protection CSRF, échappement XSS,
  `password_hash`, sessions durcies, rate limiting, uploads protégés.
- 🚀 **SEO** : URLs propres, meta, Open Graph, Schema.org, `sitemap.php`,
  `robots.txt`, photos JPEG optimisées (~14 Mo au total), polices auto-hébergées.

---

## 🧪 Démarrage rapide (démo locale, SQLite — zéro configuration)

Le projet embarque un **mode SQLite** pour tester immédiatement sans installer MySQL.

```bash
# 1. Se placer dans le dossier du projet
cd bella-rosa

# 2. (Le fichier config/config.php est déjà fourni en mode sqlite)
#    Créer la base + les données de démonstration :
php database/install.php

# 3. Lancer le serveur de développement PHP
php -S localhost:8000

# 4. Ouvrir dans le navigateur
#    Boutique : http://localhost:8000/
#    Admin    : http://localhost:8000/admin/  (admin@bella-rosa.dz / admin123)
```

**Identifiants de démonstration**

| Rôle   | E-mail                  | Mot de passe |
|--------|-------------------------|--------------|
| Admin  | `admin@bella-rosa.dz`   | `admin123`   |
| Client | `client@bella-rosa.dz`  | `client123`  |

---

## 🌐 Installation sur AwardSpace Free (MySQL)

### 1. Limitations AwardSpace Free à connaître
AwardSpace Free impose (valeurs indicatives, à revérifier sur votre panneau) :

| Ressource            | Limite typique Free        | Adaptation du projet |
|----------------------|----------------------------|----------------------|
| Espace disque        | ~1 Go                      | ~14 Mo de photos de démo (`/uploads`) ; repli SVG (`assets/placeholder.php`) si une image manque. |
| Bande passante       | ~5 Go / mois               | Cache navigateur (`.htaccess`), assets légers, GZIP. |
| Bases MySQL          | **1 seule base**           | Le projet n'utilise **qu'une base** MySQL. |
| Taille upload PHP    | ~limitée (post_max_size)   | Uploads dans `/uploads` (protégés, non exécutables). |
| Version PHP          | 7.4 → 8.x                  | Compatible **PHP 7.4+ / 8.x** (testé sous 8.4). |
| Sous-domaine / FTP   | 1 site, accès FTP          | Structure mono-docroot, prête pour FTP. |

> ℹ️ Une seule base de données MySQL est requise, conformément à la limite AwardSpace.

### 2. Créer la base de données
1. Panneau AwardSpace → **MySQL Databases** → créer une base (ex. `1234567_bella`).
2. Noter : **hôte** (ex. `fdb1234.awardspace.net`), **nom de base**, **utilisateur**, **mot de passe**.
3. Ouvrir **phpMyAdmin** → sélectionner la base → onglet **Importer** →
   importer le fichier **`database/database.sql`** (schéma + 120 produits +
   50 clients + 40 commandes + blog + avis…).

### 3. Configurer le projet
```bash
cp config/config.example.php config/config.php
```
Éditer `config/config.php` :
```php
'db' => [
    'driver' => 'mysql',
    'host'   => 'fdb1234.awardspace.net',   // votre hôte MySQL
    'name'   => '1234567_bella',            // votre base
    'user'   => '1234567_bella',            // votre utilisateur
    'pass'   => 'VotreMotDePasse',
    ...
],
'security' => [
    'app_key' => '...',   // générer : php -r "echo bin2hex(random_bytes(32));"
    ...
],
```

### 4. Téléverser les fichiers
Uploader **tout le contenu du projet** à la racine web (`/`) via FTP ou le
gestionnaire de fichiers AwardSpace. Vérifier que le dossier **`/uploads`** est
accessible en écriture (chmod 755 ou 775).

### 5. Vérifier
- Boutique : `https://votre-domaine/`
- Admin : `https://votre-domaine/admin/`
- **Supprimer `database/install.php`** en production (sécurité).

---

## 📁 Structure du projet

```
/
├── index.php                 Accueil (hero, vedettes, promos, blog…)
├── shop.php                  Catalogue (filtres, tri, pagination)
├── product.php               Fiche produit (galerie, avis, similaires)
├── cart.php  checkout.php     Panier & tunnel de commande
├── wishlist.php compare.php   Favoris & comparateur
├── blog.php  blog-post.php    Blog beauté
├── about / contact / faq / page.php
├── sitemap.php  404.php  robots.txt
│
├── /admin                    Back-office (dashboard + CRUD complet)
├── /customer                 Espace client (compte, commandes, profil…)
├── /api                      Points AJAX (cart, wishlist, search, coupon…)
├── /includes                 Cœur applicatif (Database, auth, csrf, helpers,
│                             composants, header/footer, layout admin)
├── /assets
│   ├── /css   style.css · admin.css   (design system complet)
│   ├── /js    main.js                 (interactions vanilla)
│   ├── /images favicon · og-image
│   ├── /fonts  Cormorant Garamond · Jost (woff2, licence OFL)
│   └── placeholder.php                (générateur d'images SVG)
├── /uploads  products · categories · banners · blog · avatars (photos libres de droits, voir CREDITS-IMAGES.md)
├── /config   config.example.php · config.php
└── /database schema_mysql.sql · database.sql · install.php · data.php …
```

Voir **`DOCUMENTATION.md`** pour l'architecture détaillée et les choix techniques.

---

## 🎨 Personnalisation

Tout est modifiable depuis **l'administration** (`/admin/settings.php`) :
nom du site, slogan, couleurs, coordonnées, réseaux sociaux, livraison,
bandeau d'annonce, carte Google Maps, etc. Les couleurs de marque sont
centralisées dans des **variables CSS** (`assets/css/style.css`).

**Images de démo** : toutes les photos (produits, catégories, bannières, blog,
avatars) sont libres de droits (Unsplash, Pexels, Pixabay) ; les sources sont
listées dans `CREDITS-IMAGES.md`. Remplacez-les depuis l'admin (upload produit)
ou directement dans `/uploads`.

**Polices** : Cormorant Garamond (titres) et Jost (texte), servies depuis
`assets/fonts/` — aucune requête vers Google Fonts.

---

## 🔒 Sécurité — points clés
- **PDO** exclusivement, **requêtes préparées** partout (aucune concaténation SQL).
- **CSRF** : jeton de session sur tous les formulaires et appels AJAX.
- **XSS** : échappement systématique via `e()` / `h()`.
- **Mots de passe** : `password_hash()` (bcrypt).
- **Sessions** durcies (HttpOnly, SameSite), régénération à la connexion.
- **Rate limiting** sur connexion, avis, newsletter, contact.
- **Uploads** : exécution PHP bloquée dans `/uploads` via `.htaccess`.

---

## 📦 Technologies
PHP 7.4+/8.x · MySQL 8 (ou SQLite pour la démo) · HTML5 · CSS3
(variables, grid, flexbox, glassmorphism) · JavaScript Vanilla (Fetch,
IntersectionObserver). **Aucune dépendance externe, aucun build.**

---

## 🌍 Internationalisation
Interface en **français** (marché algérien), architecture prête pour
**arabe (RTL)** et **anglais** : textes centralisables, `dir`/`lang`
paramétrables, structure de gabarits découplée.

---

© Bella Rosa — Démonstration e-commerce. Conçu avec ♥.
