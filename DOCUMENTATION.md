# Documentation technique — Bella Rosa

Ce document décrit l'architecture, les choix de conception et le fonctionnement
interne du projet. Il complète le `README.md` (installation).

---

## 1. Philosophie & choix d'architecture

### 1.1 PHP Vanilla, sans framework
Contrainte du cahier des charges **et** atout pour AwardSpace Free : aucun
Composer, aucun build, aucune dépendance à installer. Le code s'appuie sur une
**petite couche applicative maison** (dans `/includes`) qui apporte l'essentiel
d'un micro-framework sans le poids :

| Brique               | Rôle |
|----------------------|------|
| `bootstrap.php`      | Point d'entrée commun : config, session, autodétection d'URL, chargement des helpers. |
| `Database.php`       | Singleton PDO + raccourcis (`run`, `first`, `all`, `scalar`, `insert`). Supporte **MySQL et SQLite**. |
| `functions.php`      | Helpers réutilisables (échappement, URLs, formatage monnaie/date, panier, SEO…). |
| `auth.php`           | Authentification clients & admins (sessions séparées). |
| `csrf.php`           | Jeton CSRF + rate limiting. |
| `queries.php`        | Couche « repository » des lectures produits/catégories. |
| `components.php`     | Fragments d'UI réutilisables (carte produit, fil d'Ariane, pastilles…). |
| `icons.php`          | Bibliothèque d'icônes SVG en ligne (zéro requête externe). |
| `header/footer.php`  | Gabarit public. |
| `admin_layout.php`   | Gabarit d'administration (sidebar + topbar). |

**Principe DRY** : chaque élément d'UI (carte produit, pastille de statut, fil
d'Ariane, pagination, navigation client/admin) est factorisé dans une fonction
ou un include unique. Aucune duplication de balisage.

### 1.2 Front controller léger vs pages autonomes
Plutôt qu'un routeur monolithique, chaque page est un **script autonome** qui
inclut `bootstrap.php` puis le gabarit. C'est plus simple à héberger (mapping
direct URL → fichier), plus lisible, et parfaitement adapté à un hébergement
mutualisé. Des **URLs propres** optionnelles sont fournies via `.htaccess`
(`/produit/slug`, `/categorie/slug`, `/article/slug`).

### 1.3 Portabilité MySQL / SQLite
`Database::pdo()` lit `config.db.driver`. En production (AwardSpace) : **MySQL 8**.
Pour la démo locale : **SQLite** (zéro configuration). Les requêtes sont écrites
en SQL portable ; les rares spécificités (fonctions de date du tableau de bord,
`NOW()` vs `datetime('now')`) sont gérées conditionnellement.

---

## 2. Base de données

### 2.1 Modèle relationnel
16 tables, clés étrangères, index, timestamps, **soft delete** (`deleted_at`)
sur les entités importantes (produits, catégories, marques, blog, clients).

```
settings                       paramètres clé/valeur du site
admins                         comptes back-office
brands 1───∞ products          marques
categories ┐ (auto-relation parent_id : hiérarchie)
           └──∞ products
products 1───∞ product_images  galerie
products 1───∞ reviews         avis (modération: pending/approved/rejected)
customers 1──∞ orders 1──∞ order_items
customers 1──∞ addresses
customers ∞──∞ products (wishlists)
coupons                        codes promo (percent/fixed, min, expiration)
blog_posts                     articles (SEO, tags, vues)
testimonials                   témoignages
newsletter                     abonnés
```

Index clés : `products(category_id, brand_id, price, flags)`,
`reviews(product_id, status)`, `orders(customer_id, status)`.

### 2.2 Fichiers
- `schema_mysql.sql` — schéma MySQL 8 (InnoDB, utf8mb4).
- `schema_sqlite.php` — schéma équivalent SQLite (démo).
- `data.php` — **source unique** des données de démonstration, générées de
  façon **déterministe** (`mt_srand` fixe) : ~120 produits, 10 marques,
  16 catégories, 25 articles, 50 clients, 40 commandes, avis, coupons…
- `seed_map.php` — mappe `data.php` → plan d'insertion (colonnes + lignes).
- `install.php` — assistant d'installation (web ou CLI), MySQL ou SQLite.
- `build_sql.php` — génère le livrable `database.sql` (schéma + INSERT).
- `database.sql` — **fichier prêt à importer** dans phpMyAdmin.

> Régénérer le SQL après modification des données de démo :
> `php database/build_sql.php`

---

## 3. Sécurité

| Menace              | Contre-mesure |
|---------------------|---------------|
| Injection SQL       | PDO + **requêtes préparées** exclusivement (`Database::run`). |
| XSS                 | Échappement systématique `e()`/`h()` en sortie. |
| CSRF                | Jeton de session (`csrf_field()` / `csrf_check()`) sur tous les POST et AJAX. |
| Vol de session      | Cookies `HttpOnly` + `SameSite=Lax`, `session_regenerate_id` à la connexion. |
| Force brute         | `rate_limit()` sur connexion, avis, newsletter, contact. |
| Mots de passe       | `password_hash()` (bcrypt) + `password_verify()`. |
| Upload malveillant  | Exécution PHP bloquée dans `/uploads` (`.htaccess`). |
| Exposition config   | `config.php` et `*.sql`/`*.sqlite` interdits d'accès (`.htaccess`). |

---

## 4. Front-end

### 4.1 Design system (`assets/css/style.css`)
- **Variables CSS** centralisées (couleurs de marque rose poudré/or, rayons,
  ombres, dégradés, typographies) — thème modifiable en un endroit.
- **Mode sombre** via `:root[data-theme="dark"]` (bascule persistée en
  `localStorage`, appliquée avant peinture pour éviter le flash).
- **Responsive** mobile-first, testé de **320px à 2560px** (breakpoints à
  1080 / 960 / 720 / 600 / 480px). Grilles fluides, aucune scrollbar horizontale.
- Effets premium : glassmorphism (header, cartes flottantes), animations douces,
  micro-interactions au survol, `skeleton` loading, révélation au défilement.
- **Accessibilité** : focus visibles, `prefers-reduced-motion`, contrastes,
  attributs ARIA sur les composants interactifs.

### 4.2 JavaScript (`assets/js/main.js`) — Vanilla, ~1 fichier
Objet `Bella` exposant : panier AJAX (Fetch + CSRF), favoris, comparateur
(localStorage), recherche instantanée (debounce), quick view (modale +
skeleton), toasts, newsletter, slider hero, compte à rebours, thème sombre,
menu mobile, révélation au défilement (`IntersectionObserver` + repli),
retour en haut. **Amélioration progressive** : le contenu reste visible sans JS.

### 4.3 Images sans poids : `assets/placeholder.php`
Génère à la volée des visuels **SVG élégants** (dégradés doux, monogramme,
motif pétale) déterministes par produit/catégorie. Avantages : **aucune image
binaire** à héberger, **aucune requête externe**, cache navigateur, empreinte
disque et bande passante minimales — idéal pour AwardSpace Free. En production,
les vraies photos se déposent dans `/uploads` et prennent automatiquement le
relais (repli sur le placeholder si absentes).

---

## 5. API AJAX (`/api`)
Chaque endpoint inclut `bootstrap.php`, valide le CSRF sur POST et répond en
JSON (`json_response`).

| Endpoint          | Rôle |
|-------------------|------|
| `cart.php`        | add / update / remove + recalcul (sous-total, livraison, progression livraison gratuite). |
| `wishlist.php`    | toggle favoris (session + BDD si connecté). |
| `search.php`      | suggestions de recherche instantanée. |
| `quickview.php`   | fragment HTML d'aperçu rapide produit. |
| `coupon.php`      | validation et application d'un code promo. |
| `compare.php`     | tableau comparatif de produits. |
| `newsletter.php`  | inscription newsletter. |

---

## 6. Administration (`/admin`)
Tableau de bord avec **KPI** (CA, commandes, clients, produits), **graphiques**
CSS purs (barres CA sur 6 mois, donut des statuts via `conic-gradient`),
alertes (commandes en attente, avis à modérer, stock faible). CRUD complet :
- **Produits** : création/édition (prix, ancien prix, stock, marque, catégorie,
  mise en avant, composition, conseils), suppression (soft delete).
- **Catégories** : hiérarchie parent/enfant, SEO.
- **Commandes** : détail, changement de statut, **export CSV**.
- **Clients** : fiches, historique d'achats, total dépensé, points.
- **Avis** : modération (approuver / rejeter / supprimer).
- **Blog** : rédaction d'articles (HTML, tags, statut).
- **Promotions** : coupons (pourcentage/fixe, minimum, expiration, limite).
- **Paramètres** : identité, coordonnées, livraison, couleurs, réseaux, carte.

---

## 7. Paiement — architecture prête (non active)
Le champ `orders.payment_method` et l'écran de paiement acceptent déjà
`cod` (à la livraison), `baridimob`, `ccp`. L'ajout d'`edahabia`, `cib`,
`visa`, `mastercard`, `paypal` ne nécessite qu'un connecteur (redirection +
webhook) branché sur la commande existante : **aucune refonte** requise.

---

## 8. Performance
- Assets légers (CSS/JS non minifiés mais compacts, un seul JS), GZIP + cache
  via `.htaccess`.
- `loading="lazy"` sur les images, `IntersectionObserver` pour les animations.
- Requêtes indexées, sous-requête d'image principale limitée, pagination.
- Placeholders SVG (quelques Ko) au lieu de photos lourdes en démo.

---

## 9. Internationalisation (prévu)
Langue par défaut : **français**. La structure est prête pour l'**arabe (RTL)**
et l'**anglais** : `lang`/`dir` pilotés par `config.app.locale`, chaînes
centralisables, gabarits découplés du contenu. L'ajout d'un fichier de
traductions et d'un sélecteur de langue suffit.

---

## 10. Pour aller plus loin (extensibilité)
Le schéma et le code sont pensés pour évoluer : variantes produit, multi-images
réelles, passerelles de paiement, e-mails transactionnels (SMTP déjà prévu dans
la config), multilingue, cartes cadeaux, cashback, chatbot… chaque ajout se
greffe sans casser l'existant.
