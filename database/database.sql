-- ============================================================================
--  La Bibliothèque Numérique : base complète (schéma + démo)
--  MySQL 8 / utf8mb4. Admin: admin@bibliotheque-numerique.dz / admin123
--  Client: client@bibliotheque-numerique.dz / client123
-- ============================================================================

-- ============================================================================
--  La Bibliothèque Numérique - Schéma MySQL 8 (utf8mb4)
--  PDO · InnoDB · clés étrangères · index · timestamps · soft delete
--  Une seule base (compatible AwardSpace Free).
-- ============================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  `key` VARCHAR(80) NOT NULL PRIMARY KEY,
  `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  icon VARCHAR(40) NULL,
  description TEXT NULL,
  image VARCHAR(190) NULL,
  seo_title VARCHAR(190) NULL,
  position INT NOT NULL DEFAULT 0,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cat_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Livres numériques ----------------------------------------------------------
DROP TABLE IF EXISTS books;
CREATE TABLE books (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(210) NOT NULL UNIQUE,
  author VARCHAR(120) NULL,
  sku VARCHAR(40) NULL,
  category_id INT UNSIGNED NULL,
  short_desc VARCHAR(400) NULL,
  long_desc TEXT NULL,
  toc TEXT NULL,                              -- sommaire (table des matières)
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  old_price DECIMAL(10,2) NULL,
  on_sale TINYINT(1) NOT NULL DEFAULT 0,
  pages_count INT NOT NULL DEFAULT 0,
  preview_pages INT NOT NULL DEFAULT 10,       -- pages d'aperçu gratuites
  language VARCHAR(40) NULL,
  file_type VARCHAR(10) NOT NULL DEFAULT 'pdf', -- 'pdf' | 'pptx' (lecteur utilisé)
  pdf_file VARCHAR(190) NULL,                   -- fichier complet (protégé) : PDF ou PPTX
  preview_file VARCHAR(190) NULL,              -- PDF d'aperçu (N premières pages) - PDF uniquement
  cover_image VARCHAR(190) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_new TINYINT(1) NOT NULL DEFAULT 0,
  is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
  rating DECIMAL(2,1) NOT NULL DEFAULT 0,
  reviews_count INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_book_cat (category_id),
  INDEX idx_book_flags (is_featured, is_new, is_bestseller, on_sale),
  INDEX idx_book_price (price),
  CONSTRAINT fk_book_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password VARCHAR(255) NOT NULL,
  wilaya VARCHAR(60) NULL,
  loyalty_points INT NOT NULL DEFAULT 0,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avis sur les livres --------------------------------------------------------
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  book_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  author_name VARCHAR(120) NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  title VARCHAR(160) NULL,
  body TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rev_book (book_id),
  INDEX idx_rev_status (status),
  CONSTRAINT fk_rev_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commandes (paiement BaridiMob manuel, prêt pour API) -----------------------
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(40) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NULL,
  customer_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method VARCHAR(30) NOT NULL DEFAULT 'baridimob',
  payment_ref VARCHAR(120) NULL,               -- réf. transaction BaridiMob (manuel)
  order_number VARCHAR(20) NULL,               -- orderNumber marchand envoyé à SATIM
  satim_order_id VARCHAR(40) NULL,             -- mdOrder / orderId généré par SATIM
  approval_code VARCHAR(12) NULL,              -- N° d'autorisation (approvalCode)
  resp_code VARCHAR(8) NULL,                   -- respCode SATIM (00 = accepté)
  pan VARCHAR(24) NULL,                        -- numéro de carte masqué
  card_brand VARCHAR(20) NULL,                 -- CIB | Edahabia
  paid_at DATETIME NULL,                       -- date/heure de la transaction acceptée
  receipt_file VARCHAR(190) NULL,              -- capture de reçu (facultatif)
  status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|awaiting|paid|refunded|cancelled
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order_customer (customer_id),
  INDEX idx_order_status (status),
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NULL,
  title VARCHAR(190) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  INDEX idx_oi_order (order_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accès aux livres (bibliothèque du client après paiement) -------------------
DROP TABLE IF EXISTS book_access;
CREATE TABLE book_access (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_access (customer_id, book_id),
  INDEX idx_access_customer (customer_id),
  CONSTRAINT fk_access_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_access_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS coupons;
CREATE TABLE coupons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  type VARCHAR(10) NOT NULL DEFAULT 'percent',
  value DECIMAL(10,2) NOT NULL DEFAULT 0,
  min_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  description VARCHAR(190) NULL,
  expires_at DATE NULL,
  usage_limit INT NOT NULL DEFAULT 0,
  used INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS blog_posts;
CREATE TABLE blog_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(210) NOT NULL UNIQUE,
  category VARCHAR(60) NULL,
  excerpt VARCHAR(400) NULL,
  body MEDIUMTEXT NULL,
  image VARCHAR(190) NULL,
  author VARCHAR(120) NULL,
  tags VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  views INT NOT NULL DEFAULT 0,
  published_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_blog_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS testimonials;
CREATE TABLE testimonials (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(120) NULL,
  avatar VARCHAR(190) NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  body TEXT NULL,
  position INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS wishlists;
CREATE TABLE wishlists (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_wish (customer_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS newsletter;
CREATE TABLE newsletter (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET FOREIGN_KEY_CHECKS = 0;

-- settings (15 lignes)
INSERT INTO `settings` (`key`,`value`) VALUES
('site_name','La Bibliothèque Numérique'),
('site_name_ar','المكتبة الرقمية'),
('site_tagline','Des livres et des présentations, partout avec vous.'),
('meta_description','Livres et présentations numériques (PDF et PowerPoint) : culture, formation, développement personnel et savoir-faire. Aperçu gratuit, achat sécurisé par BaridiMob, lecture en ligne immédiate.'),
('contact_email','contact@bibliotheque-numerique.dz'),
('contact_phone','+213 550 12 34 56'),
('contact_address','Alger, Algérie'),
('default_preview_pages','10'),
('baridimob_rip','007 9999 0001 2345 6789'),
('baridimob_name','LA BIBLIOTHEQUE NUMERIQUE'),
('baridimob_note','Après votre virement BaridiMob, indiquez la référence de transaction. Notre équipe validera votre accès sous 24h.'),
('social_instagram','https://instagram.com/bibliotheque.numerique'),
('social_facebook','https://facebook.com/bibliotheque.numerique'),
('social_tiktok','https://tiktok.com/@bibliotheque.numerique'),
('announcement','Aperçu gratuit de chaque titre • Livres PDF & présentations PowerPoint • Lecture en ligne immédiate');

-- admins (1 lignes)
INSERT INTO `admins` (`name`,`email`,`password`,`role`) VALUES
('La Bibliothèque','admin@bibliotheque-numerique.dz','$2y$12$9qfYniVLm5lANgfNj1gYd.nWXyO2yVJwwPHfZPtZJU6jI6vqdj176','super');

-- categories (7 lignes)
INSERT INTO `categories` (`id`,`parent_id`,`name`,`slug`,`icon`,`description`,`seo_title`,`position`) VALUES
(1,NULL,'Business & Entrepreneuriat','business-entrepreneuriat','wallet','Nos livres, catégorie Business & Entrepreneuriat.','Business & Entrepreneuriat | La Bibliothèque Numérique',1),
(2,NULL,'Développement personnel','developpement-personnel','sparkles','Nos livres, catégorie Développement personnel.','Développement personnel | La Bibliothèque Numérique',2),
(3,NULL,'Informatique & Numérique','informatique-numerique','grid','Nos livres, catégorie Informatique & Numérique.','Informatique & Numérique | La Bibliothèque Numérique',3),
(4,NULL,'Langues & Communication','langues-communication','chat','Nos livres, catégorie Langues & Communication.','Langues & Communication | La Bibliothèque Numérique',4),
(5,NULL,'Sciences & Techniques','sciences-techniques','award','Nos livres, catégorie Sciences & Techniques.','Sciences & Techniques | La Bibliothèque Numérique',5),
(6,NULL,'Histoire & Culture','histoire-culture','book','Nos livres, catégorie Histoire & Culture.','Histoire & Culture | La Bibliothèque Numérique',6),
(7,NULL,'Présentations & Formations','presentations-formations','presentation','Nos livres, catégorie Présentations & Formations.','Présentations & Formations | La Bibliothèque Numérique',7);

-- books (10 lignes)
INSERT INTO `books` (`id`,`title`,`slug`,`author`,`sku`,`category_id`,`short_desc`,`long_desc`,`toc`,`price`,`old_price`,`on_sale`,`pages_count`,`preview_pages`,`language`,`file_type`,`pdf_file`,`preview_file`,`cover_image`,`is_featured`,`is_new`,`is_bestseller`,`rating`,`reviews_count`,`status`) VALUES
(1,'Mon projet commence aujourd''hui','mon-projet-commence-aujourd-hui','Amine Belkacem','LIV-001',1,'Un livre signé Amine Belkacem, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2000,NULL,0,20,10,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf','livre-exemple-cover.png',1,0,1,4.7,12,'active'),
(2,'Bien démarrer son activité en ligne','bien-demarrer-son-activite-en-ligne','Karim Ammar','LIV-002',1,'Un livre signé Karim Ammar, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1800,2500,1,20,8,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,1,0,0,4.9,87,'active'),
(3,'Le grand livre de la productivité','le-grand-livre-de-la-productivite','Sonia Gacem','LIV-003',2,'Un livre signé Sonia Gacem, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1500,NULL,0,20,6,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,5,56,'active'),
(4,'Prendre la parole en public','prendre-la-parole-en-public','Yacine Toumi','LIV-004',4,'Un livre signé Yacine Toumi, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1600,NULL,0,20,10,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,4.5,28,'active'),
(5,'Histoire et patrimoine algériens','histoire-et-patrimoine-algeriens','Meriem Saidi','LIV-005',6,'Un livre signé Meriem Saidi, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2200,NULL,0,20,12,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,1,0,1,4.8,34,'active'),
(6,'Excel & tableurs : le guide express','excel-tableurs-le-guide-express','Karim Ammar','LIV-006',3,'Un livre signé Karim Ammar, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1200,1600,1,20,5,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,5,69,'active'),
(7,'L''art de la présentation efficace','l-art-de-la-presentation-efficace','Lydia Kaci','LIV-007',7,'Une présentation signé Lydia Kaci, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2400,NULL,0,7,3,'Français','pptx','presentation-exemple.pptx',NULL,NULL,0,0,1,4.5,90,'active'),
(8,'Anglais professionnel en 30 jours','anglais-professionnel-en-30-jours','Sarah Meziane','LIV-008',4,'Un livre signé Sarah Meziane, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',900,1300,1,20,6,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,4.6,36,'active'),
(9,'Introduction aux sciences des données','introduction-aux-sciences-des-donnees','Feriel Cherif','LIV-009',5,'Un livre signé Feriel Cherif, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1700,NULL,0,20,10,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,1,0,0,4.9,70,'active'),
(10,'Formation : gérer une équipe','formation-gerer-une-equipe','Rania Lounis','LIV-010',7,'Une présentation signé Rania Lounis, aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction : pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2000,NULL,0,6,2,'Français','pptx','formation-equipe-exemple.pptx',NULL,NULL,1,0,1,4.9,14,'active');

-- reviews (33 lignes)
INSERT INTO `reviews` (`id`,`book_id`,`customer_id`,`author_name`,`rating`,`title`,`body`,`status`,`created_at`) VALUES
(1,1,NULL,'Amina B.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-06-25 14:14:24'),
(2,1,NULL,'Feriel D.',4,'Coup de cœur !','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-04-09 12:20:26'),
(3,1,NULL,'Katia S.',4,'Excellent','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-03-29 17:22:10'),
(4,1,NULL,'Amina B.',4,'Excellent','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-07-15 05:11:41'),
(5,1,NULL,'Katia S.',5,'Coup de cœur !','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-08-21 12:18:56'),
(6,2,NULL,'Feriel D.',4,'Excellent','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-04-09 16:28:54'),
(7,2,NULL,'Nesrine T.',5,'Coup de cœur !','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-03-29 11:21:32'),
(8,3,NULL,'Lydia K.',5,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-09-19 20:14:36'),
(9,3,NULL,'Yasmine H.',4,'Parfait pour débuter','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-03 01:19:53'),
(10,3,NULL,'Feriel D.',4,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-04-05 16:41:15'),
(11,4,NULL,'Lydia K.',4,'Je recommande','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-09-11 17:47:57'),
(12,4,NULL,'Nesrine T.',5,'Excellent','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-22 02:23:34'),
(13,4,NULL,'Nesrine T.',4,'Parfait pour débuter','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-07-28 18:15:59'),
(14,5,NULL,'Yasmine H.',4,'Coup de cœur !','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-08-17 08:37:47'),
(15,5,NULL,'Lydia K.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-06-07 19:33:27'),
(16,5,NULL,'Rania L.',5,'Excellent','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-09-13 06:27:59'),
(17,6,NULL,'Katia S.',4,'Je recommande','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-06-08 05:41:09'),
(18,6,NULL,'Feriel D.',4,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','pending','2026-05-06 15:00:30'),
(19,7,NULL,'Sonia G.',5,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-07-17 21:25:20'),
(20,7,NULL,'Rania L.',5,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-08-24 09:20:50'),
(21,7,NULL,'Sonia G.',4,'Parfait pour débuter','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-06-06 10:19:44'),
(22,7,NULL,'Katia S.',4,'Très complet','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-09-05 13:50:12'),
(23,7,NULL,'Amina B.',4,'Très complet','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-08-12 22:05:14'),
(24,8,NULL,'Feriel D.',5,'Parfait pour débuter','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-06-23 01:53:07'),
(25,8,NULL,'Katia S.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-09-06 10:45:01'),
(26,8,NULL,'Lydia K.',5,'Je recommande','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-05-22 14:40:45'),
(27,8,NULL,'Katia S.',4,'Très complet','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-07-12 03:02:13'),
(28,8,NULL,'Yasmine H.',5,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-06-14 19:31:40'),
(29,9,NULL,'Amina B.',4,'Excellent','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','pending','2026-05-17 21:46:22'),
(30,9,NULL,'Yasmine H.',4,'Coup de cœur !','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-07-13 19:52:47'),
(31,10,NULL,'Nesrine T.',4,'Très complet','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-05-22 23:46:37'),
(32,10,NULL,'Feriel D.',5,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-06-22 17:07:58'),
(33,10,NULL,'Rania L.',4,'Parfait pour débuter','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','pending','2026-07-15 23:42:21');

-- customers (30 lignes)
INSERT INTO `customers` (`id`,`first_name`,`last_name`,`email`,`phone`,`password`,`wilaya`,`loyalty_points`,`created_at`) VALUES
(1,'Yasmine','Demo','client@bibliotheque-numerique.dz','0671106498','$2y$12$JmmLk0bcZGGxbHXUABFf4.skL/L9WsVvMtOurAGxjSqHkkEBU7dfS','Alger',0,'2026-07-22 21:12:11'),
(2,'Salima','Benali','salima.benali2@email.dz','0760750138','$2y$12$Pu2MNhDJMqhC8wgU9jozIui5EAhnZsraGSsc7bSq8zekrDta78kJG','Alger',0,'2026-08-19 14:13:38'),
(3,'Sarah','Cherif','sarah.cherif3@email.dz','0504232163','$2y$12$baucQ0gBb/HV4TGOjK2mluwuv4tVDByNcQOFRHoYZvdHrNdjnA0U.','Alger',0,'2026-03-26 23:11:20'),
(4,'Hana','Toumi','hana.toumi4@email.dz','0764817267','$2y$12$ly6oCyQyubO3KuUYY2.aMum0wGTDdsXs4B7nt8ugNWWlkihpEaWl.','Alger',0,'2026-01-18 22:00:43'),
(5,'Feriel','Gacem','feriel.gacem5@email.dz','0569071363','$2y$12$Mh1QplhZ7OoXGlCkM.xHue7gUF5Lp/.03QxaCHz8c3lP3kJG3BCXe','Alger',0,'2026-01-27 13:01:01'),
(6,'Amina','Haddad','amina.haddad6@email.dz','0700222760','$2y$12$w873ktltPi.9ra82j6v1h./J2Q5ewOXWVx6aGSpWRIOPwqiXubAd6','Alger',0,'2026-05-30 08:01:41'),
(7,'Ines','Saidi','ines.saidi7@email.dz','0537058832','$2y$12$8FziQfDOygkqligLiCrEe.6.6gwxwczneIF1CccuhBtR0zwp/QqGm','Alger',0,'2026-03-27 10:54:30'),
(8,'Ines','Toumi','ines.toumi8@email.dz','0641186247','$2y$12$PUxyQVa.gic2JBnogdbUxe.FwdvkHKQSE0VVAkRjj7s0rV/9UW8oC','Alger',0,'2026-08-09 22:38:16'),
(9,'Feriel','Ferhat','feriel.ferhat9@email.dz','0509991721','$2y$12$lzZ57IVcS9Eb0.tjKJKS3Ol7m53frtoLBN9XFiXXLZuhgeH11iyym','Alger',0,'2026-06-07 06:24:11'),
(10,'Rania','Kaci','rania.kaci10@email.dz','0512725464','$2y$12$ZPsCofa2/W9H1IJ08IMHC.w2ALgOIgDxO4VEIG/Zohdj6GLtLBHqi','Alger',0,'2026-04-16 13:00:07'),
(11,'Ines','Kaci','ines.kaci11@email.dz','0685316961','$2y$12$YscsDdSpS9nErg/hgwKLyecp3EjYdHuRlz/yRRdmcF24ww2TSKqEW','Alger',0,'2026-05-20 00:25:33'),
(12,'Katia','Saidi','katia.saidi12@email.dz','0780025025','$2y$12$Mr2ywq7wyPL7vmwxK2szguZr2DDqUD5OEcHMQK13ZVkJ3GCsTeyWK','Alger',0,'2026-06-17 11:39:26'),
(13,'Feriel','Meziane','feriel.meziane13@email.dz','0747219824','$2y$12$zdbgDxYzkXAzM7O5BCVltuYZbuGmuxOY7JZn96A0BpTiLfmoch/U6','Alger',0,'2026-06-23 03:21:20'),
(14,'Hana','Lounis','hana.lounis14@email.dz','0720134766','$2y$12$bH7g7j2aFRZ2SGHxJkOLou7zN1X8J.3nNBDFKDYpi0bpbCJGuyI72','Alger',0,'2026-04-03 09:35:32'),
(15,'Meriem','Lounis','meriem.lounis15@email.dz','0741525932','$2y$12$TuV8InsCTC5stCpZ/io2GeXBj1Pa8Drs.b7DdjvtSlaiH9tCrnmyi','Alger',0,'2026-07-22 00:46:22'),
(16,'Salima','Ferhat','salima.ferhat16@email.dz','0785280825','$2y$12$pqtXgQUrffZOyDcuTpaE8OfxKY9YZG3YBa4o8PlSk3IRKj4CB8Y52','Alger',0,'2026-07-10 02:49:09'),
(17,'Lina','Rahmani','lina.rahmani17@email.dz','0507206626','$2y$12$bUoNBz3v1zCl9t7v4iZZLuwsju0gikWACqxwz16nYyj374jc4ZQnG','Alger',0,'2026-01-18 12:38:17'),
(18,'Feriel','Toumi','feriel.toumi18@email.dz','0684749559','$2y$12$XQBTE9C5uKz.8Jj1O7qQY.L.XK3lWWL.snvb6LSM8vxDKPCvqWdMa','Alger',0,'2026-05-19 00:05:26'),
(19,'Ines','Cherif','ines.cherif19@email.dz','0647173112','$2y$12$U1FvHfpqvLHbzJqsJiu3ReakAgw5aaxBHxxt.lkC4zj6L2efaN.6u','Alger',0,'2026-03-28 20:53:04'),
(20,'Rania','Meziane','rania.meziane20@email.dz','0762488271','$2y$12$pdYZrqWW5ehkSIfleRvxd.v0KMVppIQiZ.bCEUGif4E9WvJciWCqu','Alger',0,'2026-03-29 02:19:17'),
(21,'Salima','Haddad','salima.haddad21@email.dz','0686599006','$2y$12$sN0hY4hLjy8su6vMrrZXauGdN359qfZxWkwKXOFE6CGOSbpcxXXMq','Alger',0,'2026-03-12 09:25:40'),
(22,'Hana','Kaci','hana.kaci22@email.dz','0577981785','$2y$12$Ly/naKZrRZriO3FwUZGETeXDb7iAW2tpOtFEEsjPwqERW1yp.APPu','Alger',0,'2026-08-25 12:22:10'),
(23,'Dalia','Kaci','dalia.kaci23@email.dz','0702975434','$2y$12$.tgQFHDIp01G8KSaCBd2..7Fxt8EWpIYcUWVKeVHevUwkGxQ6wim.','Alger',0,'2026-02-02 01:18:50'),
(24,'Feriel','Toumi','feriel.toumi24@email.dz','0695569495','$2y$12$A2HR9aw3d0sj4R8xEWWzBuNdqAADx5hBc/O0uY1845krCRXv9luc2','Alger',0,'2026-04-04 20:49:58'),
(25,'Nadia','Lounis','nadia.lounis25@email.dz','0610416760','$2y$12$dG50QBQkebw4vedG4crTfO6fUn0XdaZbcfNkF7SZrUsC62.MvjeF.','Alger',0,'2026-03-10 21:51:12'),
(26,'Nesrine','Saidi','nesrine.saidi26@email.dz','0626832218','$2y$12$wupuwt/E.f4TnamboklZsOrtLhHDKPIDJo8pmjiGLCvRBUx6xWWP.','Alger',0,'2026-01-30 22:24:25'),
(27,'Lina','Rahmani','lina.rahmani27@email.dz','0679616681','$2y$12$pijk9zk9yN9Cz0dprFXM8ebl5fGvuZC/z9FuTkb/KRex7R.uI6zWe','Alger',0,'2026-05-11 15:03:37'),
(28,'Rania','Rahmani','rania.rahmani28@email.dz','0643851057','$2y$12$X7.gHnRbVr/.TXYdmxml9OFtO1Fbb4.91zSIPsuJwvHQo3fbFFsti','Alger',0,'2026-08-13 00:38:28'),
(29,'Hana','Benali','hana.benali29@email.dz','0606500241','$2y$12$1KtdQ8exBdshsYwiQauQZeT9lOL7XCpDkHemYl/ofcIWRGq2CTuf.','Alger',0,'2026-07-02 22:59:47'),
(30,'Meriem','Meziane','meriem.meziane30@email.dz','0564356177','$2y$12$kPSZColHOCIiZx87X0/I8e8qtKCBDYKozIPy4LIMmjsrrN1FqCsH2','Alger',0,'2026-04-04 22:12:34');

-- orders (25 lignes)
INSERT INTO `orders` (`id`,`reference`,`customer_id`,`customer_name`,`email`,`phone`,`subtotal`,`discount`,`total`,`payment_method`,`payment_ref`,`receipt_file`,`status`,`note`,`created_at`) VALUES
(1,'CMD-2026-0001',8,'Ines Toumi','ines.toumi8@email.dz','0641186247',1500,0,1500,'baridimob','','','pending','','2026-07-09 07:04:39'),
(2,'CMD-2026-0002',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',1500,0,1500,'baridimob','384224651','','paid','','2026-06-20 15:12:02'),
(3,'CMD-2026-0003',9,'Feriel Ferhat','feriel.ferhat9@email.dz','0509991721',2400,0,2400,'baridimob','211760988','','awaiting','','2026-07-19 08:54:04'),
(4,'CMD-2026-0004',25,'Nadia Lounis','nadia.lounis25@email.dz','0610416760',3800,0,3800,'baridimob','976313142','','paid','','2026-09-04 17:52:50'),
(5,'CMD-2026-0005',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',900,0,900,'baridimob','905929618','','paid','','2026-08-03 13:54:58'),
(6,'CMD-2026-0006',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',2700,0,2700,'baridimob','721281479','','paid','','2026-09-19 23:03:35'),
(7,'CMD-2026-0007',24,'Feriel Toumi','feriel.toumi24@email.dz','0695569495',3200,0,3200,'baridimob','866273529','','paid','','2026-07-05 23:57:09'),
(8,'CMD-2026-0008',8,'Ines Toumi','ines.toumi8@email.dz','0641186247',4400,0,4400,'baridimob','588748665','','awaiting','','2026-08-25 19:34:29'),
(9,'CMD-2026-0009',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',3700,0,3700,'baridimob','714324788','','cancelled','','2026-08-06 12:32:17'),
(10,'CMD-2026-0010',1,'Yasmine Demo','client@bibliotheque-numerique.dz','0671106498',3500,0,3500,'baridimob','961810826','','paid','','2026-07-31 01:30:56'),
(11,'CMD-2026-0011',26,'Nesrine Saidi','nesrine.saidi26@email.dz','0626832218',3500,0,3500,'baridimob','818112106','','paid','','2026-06-27 03:31:04'),
(12,'CMD-2026-0012',21,'Salima Haddad','salima.haddad21@email.dz','0686599006',1800,0,1800,'baridimob','297751897','','paid','','2026-07-13 10:17:22'),
(13,'CMD-2026-0013',1,'Yasmine Demo','client@bibliotheque-numerique.dz','0671106498',4200,0,4200,'baridimob','','','pending','','2026-08-23 20:33:09'),
(14,'CMD-2026-0014',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',900,0,900,'baridimob','996629564','','paid','','2026-09-09 16:28:22'),
(15,'CMD-2026-0015',2,'Salima Benali','salima.benali2@email.dz','0760750138',1200,0,1200,'baridimob','225236163','','awaiting','','2026-07-25 21:00:31'),
(16,'CMD-2026-0016',7,'Ines Saidi','ines.saidi7@email.dz','0537058832',3400,0,3400,'baridimob','542885123','','paid','','2026-09-07 06:31:33'),
(17,'CMD-2026-0017',12,'Katia Saidi','katia.saidi12@email.dz','0780025025',2400,0,2400,'baridimob','265330663','','paid','','2026-08-03 07:12:41'),
(18,'CMD-2026-0018',15,'Meriem Lounis','meriem.lounis15@email.dz','0741525932',900,0,900,'baridimob','390882781','','paid','','2026-07-31 06:26:06'),
(19,'CMD-2026-0019',27,'Lina Rahmani','lina.rahmani27@email.dz','0679616681',1800,0,1800,'baridimob','','','pending','','2026-09-15 11:59:30'),
(20,'CMD-2026-0020',21,'Salima Haddad','salima.haddad21@email.dz','0686599006',3700,0,3700,'baridimob','276768373','','paid','','2026-06-27 11:40:51'),
(21,'CMD-2026-0021',25,'Nadia Lounis','nadia.lounis25@email.dz','0610416760',1800,0,1800,'baridimob','','','pending','','2026-07-10 09:12:02'),
(22,'CMD-2026-0022',18,'Feriel Toumi','feriel.toumi18@email.dz','0684749559',2200,0,2200,'baridimob','144614117','','awaiting','','2026-09-19 15:14:32'),
(23,'CMD-2026-0023',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',4400,0,4400,'baridimob','798214567','','paid','','2026-08-11 10:40:51'),
(24,'CMD-2026-0024',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',2800,0,2800,'baridimob','430851034','','paid','','2026-08-10 14:25:46'),
(25,'CMD-2026-0025',22,'Hana Kaci','hana.kaci22@email.dz','0577981785',2900,0,2900,'baridimob','','','pending','','2026-07-25 20:56:22');

-- order_items (39 lignes)
INSERT INTO `order_items` (`id`,`order_id`,`book_id`,`title`,`price`) VALUES
(1,1,3,'Le grand livre de la productivité',1500),
(2,2,3,'Le grand livre de la productivité',1500),
(3,3,3,'Le grand livre de la productivité',1500),
(4,3,8,'Anglais professionnel en 30 jours',900),
(5,4,4,'Prendre la parole en public',1600),
(6,4,5,'Histoire et patrimoine algériens',2200),
(7,5,8,'Anglais professionnel en 30 jours',900),
(8,6,2,'Bien démarrer son activité en ligne',1800),
(9,6,8,'Anglais professionnel en 30 jours',900),
(10,7,6,'Excel & tableurs : le guide express',1200),
(11,7,10,'Formation : gérer une équipe',2000),
(12,8,1,'Mon projet commence aujourd''hui',2000),
(13,8,7,'L''art de la présentation efficace',2400),
(14,9,1,'Mon projet commence aujourd''hui',2000),
(15,9,9,'Introduction aux sciences des données',1700),
(16,10,1,'Mon projet commence aujourd''hui',2000),
(17,10,3,'Le grand livre de la productivité',1500),
(18,11,1,'Mon projet commence aujourd''hui',2000),
(19,11,3,'Le grand livre de la productivité',1500),
(20,12,2,'Bien démarrer son activité en ligne',1800),
(21,13,2,'Bien démarrer son activité en ligne',1800),
(22,13,7,'L''art de la présentation efficace',2400),
(23,14,8,'Anglais professionnel en 30 jours',900),
(24,15,6,'Excel & tableurs : le guide express',1200),
(25,16,5,'Histoire et patrimoine algériens',2200),
(26,16,6,'Excel & tableurs : le guide express',1200),
(27,17,7,'L''art de la présentation efficace',2400),
(28,18,8,'Anglais professionnel en 30 jours',900),
(29,19,2,'Bien démarrer son activité en ligne',1800),
(30,20,1,'Mon projet commence aujourd''hui',2000),
(31,20,9,'Introduction aux sciences des données',1700),
(32,21,2,'Bien démarrer son activité en ligne',1800),
(33,22,5,'Histoire et patrimoine algériens',2200),
(34,23,1,'Mon projet commence aujourd''hui',2000),
(35,23,7,'L''art de la présentation efficace',2400),
(36,24,4,'Prendre la parole en public',1600),
(37,24,6,'Excel & tableurs : le guide express',1200),
(38,25,8,'Anglais professionnel en 30 jours',900),
(39,25,10,'Formation : gérer une équipe',2000);

-- book_access (23 lignes)
INSERT INTO `book_access` (`id`,`customer_id`,`book_id`,`order_id`,`created_at`) VALUES
(1,3,3,2,'2026-06-20 15:12:02'),
(2,25,4,4,'2026-09-04 17:52:50'),
(3,25,5,4,'2026-09-04 17:52:50'),
(4,11,8,5,'2026-08-03 13:54:58'),
(5,11,2,6,'2026-09-19 23:03:35'),
(6,24,6,7,'2026-07-05 23:57:09'),
(7,24,10,7,'2026-07-05 23:57:09'),
(8,1,1,10,'2026-07-31 01:30:56'),
(9,1,3,10,'2026-07-31 01:30:56'),
(10,26,1,11,'2026-06-27 03:31:04'),
(11,26,3,11,'2026-06-27 03:31:04'),
(12,21,2,12,'2026-07-13 10:17:22'),
(13,3,8,14,'2026-09-09 16:28:22'),
(14,7,5,16,'2026-09-07 06:31:33'),
(15,7,6,16,'2026-09-07 06:31:33'),
(16,12,7,17,'2026-08-03 07:12:41'),
(17,15,8,18,'2026-07-31 06:26:06'),
(18,21,1,20,'2026-06-27 11:40:51'),
(19,21,9,20,'2026-06-27 11:40:51'),
(20,11,1,23,'2026-08-11 10:40:51'),
(21,11,7,23,'2026-08-11 10:40:51'),
(22,3,4,24,'2026-08-10 14:25:46'),
(23,3,6,24,'2026-08-10 14:25:46');

-- coupons (5 lignes)
INSERT INTO `coupons` (`id`,`code`,`type`,`value`,`min_amount`,`description`,`expires_at`,`usage_limit`,`used`,`active`) VALUES
(1,'BIENVENUE10','percent',10,0,'-10% première commande','2026-10-21',93,29,1),
(2,'LECTURE15','percent',15,3000,'-15% dès 3000 DA','2027-01-13',54,10,1),
(3,'LECTEUR500','fixed',500,2000,'-500 DA','2026-10-21',146,28,1),
(4,'RAMADAN20','percent',20,4000,'Offre Ramadan -20%','2026-11-25',139,2,1),
(5,'GRATUIT','fixed',300,900,'-300 DA','2026-10-31',142,9,1);

-- blog_posts (8 lignes)
INSERT INTO `blog_posts` (`id`,`title`,`slug`,`category`,`excerpt`,`body`,`image`,`author`,`tags`,`status`,`views`,`published_at`) VALUES
(1,'Comment lire plus régulièrement','comment-lire-plus-regulierement','Conseils','Mes conseils pour comment lire plus régulièrement.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',249,'2026-05-06 02:24:02'),
(2,'5 livres pour bien démarrer une activité','5-livres-pour-bien-demarrer-une-activite','Conseils','Mes conseils pour 5 livres pour bien démarrer une activité.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',3176,'2026-06-08 12:36:20'),
(3,'Construire une présentation qui convainc','construire-une-presentation-qui-convainc','Tutoriels','Mes conseils pour construire une présentation qui convainc.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',2977,'2026-08-13 01:01:25'),
(4,'Prendre des notes efficacement','prendre-des-notes-efficacement','Tutoriels','Mes conseils pour prendre des notes efficacement.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',1097,'2026-06-10 08:47:42'),
(5,'Organiser sa bibliothèque numérique','organiser-sa-bibliotheque-numerique','Conseils','Mes conseils pour organiser sa bibliothèque numérique.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',3024,'2026-06-10 04:46:28'),
(6,'Les erreurs classiques d''un diaporama','les-erreurs-classiques-d-un-diaporama','Tutoriels','Mes conseils pour les erreurs classiques d''un diaporama.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',2156,'2026-06-27 03:19:23'),
(7,'PDF ou PowerPoint : quel format choisir','pdf-ou-powerpoint-quel-format-choisir','Tutoriels','Mes conseils pour pdf ou powerpoint : quel format choisir.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',346,'2026-07-06 11:31:08'),
(8,'Se former en ligne sans se disperser','se-former-en-ligne-sans-se-disperser','Business','Mes conseils pour se former en ligne sans se disperser.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Business, lecture, conseils','published',1107,'2026-07-02 01:35:25');

-- testimonials (8 lignes)
INSERT INTO `testimonials` (`id`,`name`,`role`,`avatar`,`rating`,`body`,`position`) VALUES
(1,'Salima S.','Lecteur fidèle',NULL,5,'Les explications sont limpides et la lecture en ligne est super pratique.',1),
(2,'Meriem R.','Étudiante',NULL,5,'J''ai adoré pouvoir lire un aperçu avant d''acheter. Contenu au top.',2),
(3,'Ines B.','Étudiante',NULL,5,'Je recommande à toute personne qui veut se former sérieusement.',3),
(4,'Ines S.','Étudiante',NULL,5,'Les présentations PowerPoint se lisent parfaitement dans le navigateur.',4),
(5,'Nadia A.','Lecteur fidèle',NULL,5,'Paiement BaridiMob simple et accès immédiat après validation.',5),
(6,'Katia L.','Formatrice indépendante',NULL,5,'J''ai adoré pouvoir lire un aperçu avant d''acheter. Contenu au top.',6),
(7,'Hana T.','Formatrice indépendante',NULL,5,'Paiement BaridiMob simple et accès immédiat après validation.',7),
(8,'Ines G.','Lecteur fidèle',NULL,5,'Les présentations PowerPoint se lisent parfaitement dans le navigateur.',8);

SET FOREIGN_KEY_CHECKS = 1;
