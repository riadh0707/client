# La Bibliothèque Numérique — المكتبة الرقمية

> Boutique de **livres et présentations numériques** — **PDF** et **PowerPoint** —
> pour le marché algérien. Aperçu gratuit de chaque titre · achat par **BaridiMob** ·
> **lecture en ligne** de l'intégralité après validation.
> **PHP Vanilla · MySQL 8 · HTML5 · CSS3 · JS Vanilla.**
> Optimisée pour l'hébergement **AwardSpace Free**.

![Aperçu](assets/images/og-image.svg)

---

## ✨ Ce que fait le site

- 🏠 **Vitrine premium « bibliothèque vintage »** : hero avec livre 3D, rayons,
  livres vedettes, nouveautés, promotions (compte à rebours), témoignages, carnet (blog).
- 📖 **Aperçu gratuit** : chaque titre est feuilletable — **les N premières pages**
  (PDF) ou **diapositives** (PowerPoint), nombre défini dans l'admin. Au-delà, il faut acheter.
- 📊 **Deux formats, deux lecteurs** : les **PDF** s'ouvrent dans la visionneuse du
  navigateur ; les **présentations PowerPoint (.pptx / .ppt)** sont restituées en
  **diapositives HTML** (titres, listes à puces, tableaux, images, notes du présentateur)
  par un lecteur maison — navigation clavier, tactile et miniatures, **sans PowerPoint
  installé ni conversion externe**. Les acheteurs peuvent aussi télécharger le `.pptx`.
- 🛒 **Panier & commande** avec codes promo.
- 💳 **Paiement BaridiMob** (mode manuel) : le client vire vers le RIP affiché,
  indique sa référence de transaction (ou joint une capture), notre équipe **valide**
  depuis l'admin → l'accès au livre complet est débloqué. **Architecture prête pour
  brancher l'API** dès qu'elle sera disponible (voir `includes/payment.php`).
- 📚 **Lecture en ligne** : après validation, le client lit **tout le titre** dans
  « Ma bibliothèque ». Les fichiers complets ne sont jamais accessibles en direct.
- 👤 **Comptes clients** : inscription, connexion, bibliothèque, commandes, profil, favoris.
- 🎛️ **Administration** : tableau de bord, **ajout d'un titre par simple upload du
  fichier** — PDF *ou* PowerPoint (pages/diapositives comptées et aperçu préparé
  automatiquement), prix, promotions, rayons, validation des paiements, avis,
  carnet, paramètres.

---

## 🧪 Démarrage rapide (démo locale — SQLite, zéro configuration)

Le projet embarque un **mode SQLite** + les fichiers exemples (un PDF et deux
présentations PowerPoint) pour tester tout de suite.

```bash
cd bibliotheque-numerique
php database/install.php        # crée la base + les données + l'aperçu du livre exemple
php -S localhost:8000           # lance le serveur
```
Ouvrez :
- **Site** : http://localhost:8000/
- **Admin** : http://localhost:8000/admin/ — `admin@bibliotheque-numerique.dz` / `admin123`
- **Client démo** : `client@bibliotheque-numerique.dz` / `client123` (possède déjà des titres)

> Les titres de démonstration sont réels : un PDF de 20 pages (aperçu 10 pages) et
> deux présentations PowerPoint de 7 et 6 diapositives, lisibles dans le lecteur de
> diapositives intégré.

---

## 🌐 Installation sur AwardSpace Free (MySQL)

### 1. Points d'attention AwardSpace Free
| Ressource        | Limite typique | Adaptation |
|------------------|----------------|------------|
| 1 base MySQL     | 1 seule        | Le projet n'utilise **qu'une base**. |
| Espace / bande passante | limités | Couvertures **SVG générées** (aucune image lourde). Vos PDF / PPTX sont les seuls fichiers lourds. |
| **Upload PHP**   | `upload_max_filesize` souvent 8–16 Mo | **Important** : pour uploader un gros fichier, augmentez `upload_max_filesize` **et** `post_max_size` (voir §4). |
| PHP              | 7.4 → 8.x      | Compatible (testé 8.4). Extension **zip** requise pour les .pptx (activée par défaut). |

### 2. Base de données
1. Panneau AwardSpace → **MySQL Databases** → créez une base.
2. **phpMyAdmin** → onglet **Importer** → importez `database/database.sql`
   (schéma + 10 livres + clientes + commandes + carnet).

### 3. Configuration
```bash
cp config/config.example.php config/config.php
```
Renseignez `config/config.php` (hôte, base, utilisateur, mot de passe MySQL) et
générez `app_key` : `php -r "echo bin2hex(random_bytes(32));"`. Mettez `driver => 'mysql'`.

### 4. Téléverser & régler les uploads
- Uploadez tous les fichiers à la racine web via FTP.
- Vérifiez que `/uploads` (et ses sous-dossiers `pdf`, `previews`, `covers`, `receipts`)
  est **inscriptible** (chmod 755/775).
- Pour accepter de gros fichiers, ajoutez si possible dans un `php.ini` à la racine :
  ```
  upload_max_filesize = 40M
  post_max_size = 42M
  ```
  (ou via le panneau AwardSpace « PHP settings »). Si un fichier dépasse la limite,
  l'admin affiche un message clair.
- **Supprimez `database/install.php`** en production.

### 5. Vos vrais titres
Admin → **Livres & présentations → Ajouter un titre** → renseignez le titre/prix,
choisissez le **nombre de pages / diapositives d'aperçu**, puis **uploadez le fichier** :

| Vous uploadez | Le site fait | Le visiteur voit |
|---------------|--------------|------------------|
| un **`.pdf`** | compte les pages, génère l'aperçu des N premières pages (FPDI) | la visionneuse PDF du navigateur |
| un **`.pptx`** ou **`.ppt`** | compte les diapositives et lit l'archive Office | un lecteur de diapositives HTML (N diapositives en aperçu, tout après achat) |

C'est tout : aucune conversion manuelle, aucun service externe.

---

## 💳 Activer le paiement BaridiMob automatique (plus tard)
Le paiement est aujourd'hui **manuel** (RIP + validation admin). Quand vous aurez
l'API BaridiMob :
1. Implémentez les méthodes `initiate()` / `handleCallback()` / `verify()` dans
   `includes/payment.php` (elles sont documentées et prêtes).
2. Créez l'endpoint webhook `api/payment-callback.php` qui appelle `mark_order_paid()`.
3. Admin → Paramètres → passez **« API automatique active »** à `1`.
Aucune autre modification : la commande et l'accès livre restent identiques.

---

## 📁 Structure
```
index.php  catalogue.php  livre.php  lire.php (lecteur PDF + diapositives)
book-pdf.php (flux fichier protégé)  book-slide.php (images de diapositives protégées)
cart.php  checkout.php (BaridiMob)  order-confirmation.php  wishlist.php  blog*  contact/about/faq/page
/admin      back-office (dashboard, titres + upload PDF/PPTX, commandes+validation, rayons, clients, avis, carnet, promos, paramètres)
/customer   espace client (bibliothèque, commandes, profil…)
/api        AJAX (cart, wishlist, search, coupon, quickview, newsletter)
/includes   cœur (Database, auth, csrf, functions, payment, pdf, pptx, components, header/footer, admin_layout)
/lib/vendor FPDF + FPDI (découpe PDF pur PHP, pour l'aperçu)
/assets     css (style + admin) · js · images · placeholder.php (couvertures SVG)
/uploads    covers · pdf (protégé : PDF et PPTX) · previews · receipts · blog
/config /database
```
Détails techniques : **`DOCUMENTATION.md`**.

---

## 🔒 Sécurité
PDO + requêtes préparées · CSRF · échappement XSS · `password_hash` · sessions durcies ·
rate limiting · **fichiers complets (PDF et PPTX) servis uniquement via `book-pdf.php`
avec contrôle d'accès** (les acheteurs seulement) · **images des diapositives servies par
`book-slide.php`**, qui refuse les diapositives hors de l'aperçu gratuit ·
exécution PHP bloquée dans `/uploads` · `uploads/pdf` interdit en accès direct.

---

© La Bibliothèque Numérique — المكتبة الرقمية. Conçu avec ♥ en Algérie.
