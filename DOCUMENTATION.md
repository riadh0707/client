# Documentation technique — La Bibliothèque Numérique

Complète le `README.md` (installation). Décrit l'architecture et les choix.

## 1. Pile & principes
- **PHP Vanilla**, aucun framework. Petite couche maison dans `/includes`
  (`Database` PDO, `auth`, `csrf`, `functions`, `payment`, `pdf`, `pptx`, `queries`,
  `components`, gabarits `header`/`footer`/`admin_layout`).
- **Portabilité** : `Database` supporte **MySQL** (production) et **SQLite** (démo).
- **DRY** : la carte livre, le fil d'Ariane, la pagination, la barre client/admin,
  les pastilles de statut sont factorisés.
- **Amélioration progressive** : le contenu reste visible sans JS ; les révélations
  au défilement sont activées par une classe `.js`.

## 2. Modèle de données (14 tables)
```
settings · admins · categories(rayons)
books        (title, author, price, old_price, on_sale, pages_count,
              preview_pages, file_type['pdf'|'pptx'], pdf_file, preview_file,
              cover_image, toc, flags…)
reviews      (book_id, modération pending/approved/rejected)
customers · orders(baridimob: payment_ref, receipt_file, status) · order_items
book_access  (customer_id, book_id) → bibliothèque du client après paiement
coupons · blog_posts · testimonials · wishlists · newsletter
```
Clés étrangères, index, **soft delete** (`deleted_at`) sur livres/rayons/blog/clients.
`book_access` a une contrainte **UNIQUE(customer_id, book_id)** (accès idempotent).

Fichiers : `schema_mysql.sql`, `schema_sqlite.php`, `data.php` (démo déterministe),
`seed_map.php`, `install.php`, `build_sql.php` → `database.sql`.

## 3. Cycle de vie d'un titre
1. **Upload (admin)** : `admin/book-edit.php` accepte **`.pdf`, `.pptx` et `.ppt`**
   et enregistre `file_type` en conséquence.
   - **PDF** → `includes/pdf.php` compte les pages (`pdf_page_count`) et génère
     l'**aperçu** des N premières pages (`pdf_make_preview`, via **FPDI + FPDF**
     purs PHP, `/lib/vendor`). Le PDF complet va dans `uploads/pdf/` (protégé),
     l'aperçu dans `uploads/previews/`.
   - **PowerPoint** → `includes/pptx.php` compte les diapositives
     (`pptx_slide_count`). Aucun fichier d'aperçu n'est produit : `lire.php` ne
     charge que les N premières diapositives à l'affichage. Un `.pptx` illisible
     est refusé et le fichier précédent conservé.
   Dans les deux cas, le fichier remplacé est supprimé du disque.
2. **Couverture** : image uploadée, sinon **couverture vintage SVG générée**
   (`assets/placeholder.php`) — zéro binaire, idéal AwardSpace.
3. **Aperçu (public)** : `lire.php` affiche le PDF d'aperçu dans une visionneuse,
   ou les N premières diapositives dans le lecteur de diapositives.
4. **Achat** : panier → `checkout.php` (BaridiMob) crée une commande `pending`/`awaiting`.
5. **Validation** : `admin/orders.php` → `mark_order_paid()` passe la commande à `paid`
   et insère les `book_access` → le livre s'ouvre dans « Ma bibliothèque ».
6. **Lecture complète** : `lire.php` sert le PDF complet via `book-pdf.php`, ou
   affiche toutes les diapositives ; l'acheteur peut aussi télécharger le `.pptx`.

## 4. Diffusion sécurisée des fichiers (`book-pdf.php`, `book-slide.php`)
- `?mode=preview` → sert le **PDF d'aperçu** (N pages) — public. Si absent, il est
  généré à la volée ; jamais le complet.
- `?mode=full` → vérifie `has_book_access()` ; **403** sinon. Sert le PDF complet en
  streaming `inline`.
- Les formats Office sont toujours servis en pièce jointe (`Content-Disposition:
  attachment`), jamais en `inline` : aucun rendu de contenu Office par le navigateur.
- `uploads/pdf/.htaccess` interdit l'accès direct aux fichiers complets (PDF **et**
  PPTX) ; tout passe par ce point contrôlé.
- `book-slide.php?slug&slide&i` diffuse **une image d'une diapositive** et refuse
  (403) toute diapositive au-delà de l'aperçu gratuit pour un non-acheteur.

## 4 bis. Lecture des présentations (`includes/pptx.php`)
Un `.pptx` est une archive ZIP de XML Office Open XML. Le module l'ouvre avec
**ZipArchive** (disponible en hébergement mutualisé) et en extrait, en PHP pur :
l'ordre réel des diapositives (`ppt/_rels/presentation.xml.rels`, repli sur un tri
numérique), le titre (placeholder `title`/`ctrTitle`), les paragraphes avec leur
niveau d'indentation, les tableaux (`a:tbl`), les images (résolues via les relations
de chaque diapositive) et les notes du présentateur. `lire.php` restitue le tout en
HTML : une diapositive visible à la fois, navigation par boutons, miniatures,
flèches du clavier (`←`/`→`, `Home`/`End`) et balayage tactile.
**Aucune conversion externe** (LibreOffice, API) n'est requise ; si le fichier est
illisible ou l'extension `zip` absente, le lecteur affiche un message clair et
propose le téléchargement aux acheteurs.

## 5. Paiement BaridiMob (`includes/payment.php`)
`BaridimobGateway` encapsule le paiement. **Mode manuel** actuel : instructions
(RIP, bénéficiaire) affichées au checkout, référence de transaction saisie par le
client, validation manuelle par l'admin. **Passage à l'API** : implémenter
`initiate()`/`handleCallback()`/`verify()`, ajouter `api/payment-callback.php`
appelant `mark_order_paid()`, puis régler `baridimob_api_enabled=1`. Le reste de
l'application ne change pas.

## 6. Front-end
Design « bibliothèque vintage » : parchemin texturé (SVG inline), bruns chauds,
or, accents rouge, ornements de coin, typographie **Cormorant Garamond** + Amiri
(arabe). Variables CSS centralisées, **mode sombre**, responsive **320→2560px**
(aucune scrollbar horizontale), glassmorphism, animations douces, skeleton, toasts.
Lecteur PDF via `<iframe>` (visionneuse native) et lecteur de diapositives maison
(HTML/CSS/JS, `.deck` / `.slide` dans `style.css`) — zéro dépendance externe.
JS Vanilla (`assets/js/main.js`, objet `Biblio`) : recherche instantanée, panier
AJAX, favoris, quick view, compte à rebours, thème, menu mobile.

## 7. Sécurité
PDO + requêtes préparées ; CSRF sur formulaires et AJAX ; échappement `e()`/`h()` ;
`password_hash` ; sessions HttpOnly/SameSite + régénération ; rate limiting ;
uploads validés (extension/taille, et ouverture réelle du `.pptx`) et non
exécutables ; fichiers complets sous accès contrôlé. Le dépassement de `post_max_size` est détecté et signalé clairement.

## 8. Différences assumées vs une boutique classique
Produits physiques → **livres numériques** : pas de stock, pas de quantité (1 par
livre), pas de livraison ni d'adresse ; « panier » simplifié ; l'après-vente est le
**droit de lecture** (`book_access`) et non un colis ; l'aperçu remplace la fiche
« essai ». Le paiement est **local (BaridiMob)** plutôt qu'à la livraison.

## 9. Extensibilité
Multi-auteurs, packs de livres, cartes cadeaux, e-mails transactionnels (SMTP prévu
en config), multilingue (fr/ar RTL/en — la structure `lang`/`dir` est prête), API de
paiement — chaque ajout se greffe sans refonte.
