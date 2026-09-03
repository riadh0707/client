-- ============================================================================
--  La Bibliothèque Numérique — base complète (schéma + démo)
--  MySQL 8 / utf8mb4. Admin: admin@bibliotheque-numerique.dz / admin123
--  Client: client@bibliotheque-numerique.dz / client123
-- ============================================================================

-- ============================================================================
--  La Bibliothèque Numérique — Schéma MySQL 8 (utf8mb4)
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
  preview_file VARCHAR(190) NULL,              -- PDF d'aperçu (N premières pages) — PDF uniquement
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
  payment_ref VARCHAR(120) NULL,               -- référence de transaction BaridiMob
  receipt_file VARCHAR(190) NULL,              -- capture de reçu (facultatif)
  status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|awaiting|paid|cancelled
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
('La Bibliothèque','admin@bibliotheque-numerique.dz','$2y$12$oB/USFN7ytqCQtmvvS1i1OVJZvquVv07KfyUwbxqhhiVEFDytcuEO','super');

-- categories (7 lignes)
INSERT INTO `categories` (`id`,`parent_id`,`name`,`slug`,`icon`,`description`,`seo_title`,`position`) VALUES
(1,NULL,'Business & Entrepreneuriat','business-entrepreneuriat','wallet','Nos livres — Business & Entrepreneuriat.','Business & Entrepreneuriat | La Bibliothèque Numérique',1),
(2,NULL,'Développement personnel','developpement-personnel','sparkles','Nos livres — Développement personnel.','Développement personnel | La Bibliothèque Numérique',2),
(3,NULL,'Informatique & Numérique','informatique-numerique','grid','Nos livres — Informatique & Numérique.','Informatique & Numérique | La Bibliothèque Numérique',3),
(4,NULL,'Langues & Communication','langues-communication','chat','Nos livres — Langues & Communication.','Langues & Communication | La Bibliothèque Numérique',4),
(5,NULL,'Sciences & Techniques','sciences-techniques','award','Nos livres — Sciences & Techniques.','Sciences & Techniques | La Bibliothèque Numérique',5),
(6,NULL,'Histoire & Culture','histoire-culture','book','Nos livres — Histoire & Culture.','Histoire & Culture | La Bibliothèque Numérique',6),
(7,NULL,'Présentations & Formations','presentations-formations','presentation','Nos livres — Présentations & Formations.','Présentations & Formations | La Bibliothèque Numérique',7);

-- books (10 lignes)
INSERT INTO `books` (`id`,`title`,`slug`,`author`,`sku`,`category_id`,`short_desc`,`long_desc`,`toc`,`price`,`old_price`,`on_sale`,`pages_count`,`preview_pages`,`language`,`file_type`,`pdf_file`,`preview_file`,`cover_image`,`is_featured`,`is_new`,`is_bestseller`,`rating`,`reviews_count`,`status`) VALUES
(1,'Mon projet commence aujourd''hui','mon-projet-commence-aujourd-hui','Amine Belkacem','LIV-001',1,'Un livre signé Amine Belkacem — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2000,NULL,0,20,10,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf','livre-exemple-cover.png',1,0,1,4.7,12,'active'),
(2,'Bien démarrer son activité en ligne','bien-demarrer-son-activite-en-ligne','Karim Ammar','LIV-002',1,'Un livre signé Karim Ammar — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1800,2500,1,20,8,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,1,0,0,4.9,87,'active'),
(3,'Le grand livre de la productivité','le-grand-livre-de-la-productivite','Sonia Gacem','LIV-003',2,'Un livre signé Sonia Gacem — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1500,NULL,0,20,6,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,5,56,'active'),
(4,'Prendre la parole en public','prendre-la-parole-en-public','Yacine Toumi','LIV-004',4,'Un livre signé Yacine Toumi — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1600,NULL,0,20,10,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,4.5,28,'active'),
(5,'Histoire et patrimoine algériens','histoire-et-patrimoine-algeriens','Meriem Saidi','LIV-005',6,'Un livre signé Meriem Saidi — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2200,NULL,0,20,12,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,1,0,1,4.8,34,'active'),
(6,'Excel & tableurs : le guide express','excel-tableurs-le-guide-express','Karim Ammar','LIV-006',3,'Un livre signé Karim Ammar — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1200,1600,1,20,5,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,5,69,'active'),
(7,'L''art de la présentation efficace','l-art-de-la-presentation-efficace','Lydia Kaci','LIV-007',7,'Une présentation signé Lydia Kaci — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2400,NULL,0,7,3,'Français','pptx','presentation-exemple.pptx',NULL,NULL,0,0,1,4.5,90,'active'),
(8,'Anglais professionnel en 30 jours','anglais-professionnel-en-30-jours','Sarah Meziane','LIV-008',4,'Un livre signé Sarah Meziane — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',900,1300,1,20,6,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,0,1,0,4.6,36,'active'),
(9,'Introduction aux sciences des données','introduction-aux-sciences-des-donnees','Feriel Cherif','LIV-009',5,'Un livre signé Feriel Cherif — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',1700,NULL,0,20,10,'Français','pdf','livre-exemple.pdf','livre-exemple.pdf',NULL,1,0,0,4.9,70,'active'),
(10,'Formation : gérer une équipe','formation-gerer-une-equipe','Rania Lounis','LIV-010',7,'Une présentation signé Rania Lounis — aperçu gratuit disponible.','Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu''à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.','Introduction — Pourquoi ce titre
Chapitre 1 : Les fondamentaux
Chapitre 2 : Bien démarrer
Chapitre 3 : La méthode pas à pas
Chapitre 4 : Outils et modèles
Chapitre 5 : Aller plus loin
Annexes & fiches pratiques',2000,NULL,0,6,2,'Français','pptx','formation-equipe-exemple.pptx',NULL,NULL,1,0,1,4.9,14,'active');

-- reviews (33 lignes)
INSERT INTO `reviews` (`id`,`book_id`,`customer_id`,`author_name`,`rating`,`title`,`body`,`status`,`created_at`) VALUES
(1,1,NULL,'Amina B.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-06-08 20:18:21'),
(2,1,NULL,'Feriel D.',4,'Coup de cœur !','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-03-23 18:24:23'),
(3,1,NULL,'Katia S.',4,'Excellent','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-03-12 23:26:07'),
(4,1,NULL,'Amina B.',4,'Excellent','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-06-28 11:15:38'),
(5,1,NULL,'Katia S.',5,'Coup de cœur !','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-08-04 18:22:53'),
(6,2,NULL,'Feriel D.',4,'Excellent','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-03-23 22:32:51'),
(7,2,NULL,'Nesrine T.',5,'Coup de cœur !','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-03-12 17:25:29'),
(8,3,NULL,'Lydia K.',5,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-09-03 02:18:33'),
(9,3,NULL,'Yasmine H.',4,'Parfait pour débuter','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-05-17 07:23:50'),
(10,3,NULL,'Feriel D.',4,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-03-19 22:45:12'),
(11,4,NULL,'Lydia K.',4,'Je recommande','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-08-25 23:51:54'),
(12,4,NULL,'Nesrine T.',5,'Excellent','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-05 08:27:31'),
(13,4,NULL,'Nesrine T.',4,'Parfait pour débuter','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-07-12 00:19:56'),
(14,5,NULL,'Yasmine H.',4,'Coup de cœur !','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-07-31 14:41:44'),
(15,5,NULL,'Lydia K.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-05-22 01:37:24'),
(16,5,NULL,'Rania L.',5,'Excellent','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-08-27 12:31:56'),
(17,6,NULL,'Katia S.',4,'Je recommande','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-05-22 11:45:06'),
(18,6,NULL,'Feriel D.',4,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','pending','2026-04-19 21:04:27'),
(19,7,NULL,'Sonia G.',5,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-07-01 03:29:17'),
(20,7,NULL,'Rania L.',5,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-08-07 15:24:47'),
(21,7,NULL,'Sonia G.',4,'Parfait pour débuter','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-05-20 16:23:41'),
(22,7,NULL,'Katia S.',4,'Très complet','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-08-19 19:54:09'),
(23,7,NULL,'Amina B.',4,'Très complet','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-07-27 04:09:11'),
(24,8,NULL,'Feriel D.',5,'Parfait pour débuter','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-06-06 07:57:04'),
(25,8,NULL,'Katia S.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-08-20 16:48:58'),
(26,8,NULL,'Lydia K.',5,'Je recommande','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-05-05 20:44:42'),
(27,8,NULL,'Katia S.',4,'Très complet','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-25 09:06:10'),
(28,8,NULL,'Yasmine H.',5,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-05-29 01:35:37'),
(29,9,NULL,'Amina B.',4,'Excellent','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','pending','2026-05-01 03:50:19'),
(30,9,NULL,'Yasmine H.',4,'Coup de cœur !','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-27 01:56:44'),
(31,10,NULL,'Nesrine T.',4,'Très complet','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-05-06 05:50:34'),
(32,10,NULL,'Feriel D.',5,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-06-05 23:11:55'),
(33,10,NULL,'Rania L.',4,'Parfait pour débuter','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','pending','2026-06-29 05:46:18');

-- customers (30 lignes)
INSERT INTO `customers` (`id`,`first_name`,`last_name`,`email`,`phone`,`password`,`wilaya`,`loyalty_points`,`created_at`) VALUES
(1,'Yasmine','Demo','client@bibliotheque-numerique.dz','0671106498','$2y$12$MRSBEyh5W62RuiJG934JWu19Pc54HriNDWMyDnPoh8dMn8ugU.aT2','Alger',0,'2026-07-06 03:16:08'),
(2,'Salima','Benali','salima.benali2@email.dz','0760750138','$2y$12$NV/G61hgPdpDf544jZh5zucBpH9kJ8gOm2ASZWslcvGeGBTiVj.L2','Alger',0,'2026-08-02 20:17:35'),
(3,'Sarah','Cherif','sarah.cherif3@email.dz','0504232163','$2y$12$vIsxhiwmyuZcov/osTvLbu8QC/zZcUPEV0PiTmI9CaSfgdzextgxa','Alger',0,'2026-03-10 05:15:17'),
(4,'Hana','Toumi','hana.toumi4@email.dz','0764817267','$2y$12$VFIoNTu2k7ack8J4VUsQZ.TxnWNJb7YCxh.PX4QN9JQI1GwQnRASe','Alger',0,'2026-01-02 04:04:40'),
(5,'Feriel','Gacem','feriel.gacem5@email.dz','0569071363','$2y$12$vE7KqdOxAtIf0O1WaxRoee.6/Mjae4fHX6kfKGAi6uDT9UKc6su4u','Alger',0,'2026-01-10 19:04:59'),
(6,'Amina','Haddad','amina.haddad6@email.dz','0700222760','$2y$12$YrVyEvk/fDI7DPYIB.rHIOnGJlKBm6lKEesoVJ9MQ/Jt7IlpXpXuK','Alger',0,'2026-05-13 14:05:38'),
(7,'Ines','Saidi','ines.saidi7@email.dz','0537058832','$2y$12$8R3o.ELGnE120zo8i.H93Opb3tpYW90X8ff79.d7KJhBDLvdR7LBq','Alger',0,'2026-03-10 16:58:27'),
(8,'Ines','Toumi','ines.toumi8@email.dz','0641186247','$2y$12$8HmfkhiJkL0J4FKvA4Ip4.J0Hv3bQ9F9iusL3raznoAn1/0PU.EUe','Alger',0,'2026-07-24 04:42:13'),
(9,'Feriel','Ferhat','feriel.ferhat9@email.dz','0509991721','$2y$12$eYNxc/T/0h7u0zqUBFQCPOM28gq4tf5isJ0ro2EKS1sLVXKzLG8l6','Alger',0,'2026-05-21 12:28:08'),
(10,'Rania','Kaci','rania.kaci10@email.dz','0512725464','$2y$12$JMPNzAKKDUnsd4oMOWn7k.DREg/nBgYBuTUC9K8FIyABEpVjE8TKi','Alger',0,'2026-03-30 19:04:04'),
(11,'Ines','Kaci','ines.kaci11@email.dz','0685316961','$2y$12$tZ1w1TOcJiOQfUCfLoco5.1drQZ7m9ieSCGf3bH6DZWnXduneeCjq','Alger',0,'2026-05-03 06:29:30'),
(12,'Katia','Saidi','katia.saidi12@email.dz','0780025025','$2y$12$axPosnHNiKlaFEECKuOX2OE.R.TxYVphxzAo9N2ga.Rg1lQlARSBq','Alger',0,'2026-05-31 17:43:23'),
(13,'Feriel','Meziane','feriel.meziane13@email.dz','0747219824','$2y$12$R7tiQekRrsGZPgrSgB2ng.bn5yVrkaPj8hokOhp9wAHkQITiB0dYO','Alger',0,'2026-06-06 09:25:17'),
(14,'Hana','Lounis','hana.lounis14@email.dz','0720134766','$2y$12$DgZrVkshLDat2WIGv/6t1OZe/EFt/Lh1ulIIR6GR/Qeg7FNysGvJC','Alger',0,'2026-03-17 15:39:29'),
(15,'Meriem','Lounis','meriem.lounis15@email.dz','0741525932','$2y$12$EZUrk0Qa9vizZqpWBiNGoO06yEdDK0M39nLl.HvDUrvrsQL/wKla6','Alger',0,'2026-07-05 06:50:19'),
(16,'Salima','Ferhat','salima.ferhat16@email.dz','0785280825','$2y$12$EnQadPL89ZGZbIDA3ZFBQuKe7QNyxEYGMsIJkJRdjRCriZOMGSXEW','Alger',0,'2026-06-23 08:53:06'),
(17,'Lina','Rahmani','lina.rahmani17@email.dz','0507206626','$2y$12$/P5aEJ6YUQ.KbL7RTNFSr.LkVQG7dqRTgpWqMSAK9tGXpBEh63dfu','Alger',0,'2026-01-01 18:42:14'),
(18,'Feriel','Toumi','feriel.toumi18@email.dz','0684749559','$2y$12$vSIkUkfIDHxrLItyV0EuzepGPGMHEwIx62tnETwCvC46uP1K5F.MO','Alger',0,'2026-05-02 06:09:24'),
(19,'Ines','Cherif','ines.cherif19@email.dz','0647173112','$2y$12$wQHo10KcTzBvE.2jN8Vn3ObFbajF9DnBjCzr9iBsmdo6Eo18swFy6','Alger',0,'2026-03-12 02:57:01'),
(20,'Rania','Meziane','rania.meziane20@email.dz','0762488271','$2y$12$NXiROv.hJHDbpSiPkwgpw.pmKWufdNi3vwZ/6A.1nZrOfh6WAGOtW','Alger',0,'2026-03-12 08:23:14'),
(21,'Salima','Haddad','salima.haddad21@email.dz','0686599006','$2y$12$RReaw79WrVQF4HawC3vjAO9rOR4x69hYPYTghjANYMr.h3Cd2hLoa','Alger',0,'2026-02-23 15:29:37'),
(22,'Hana','Kaci','hana.kaci22@email.dz','0577981785','$2y$12$AVkkTXGClQGhicJs7wi4aOV0.my.ovty2yW50esFgdAMlf7YWWbRC','Alger',0,'2026-08-08 18:26:07'),
(23,'Dalia','Kaci','dalia.kaci23@email.dz','0702975434','$2y$12$5Bhb4xrB0W0YcsAMxHB6zuZweXVQDxYC3D55yVaWOB4Ooo7.DTtj6','Alger',0,'2026-01-16 07:22:47'),
(24,'Feriel','Toumi','feriel.toumi24@email.dz','0695569495','$2y$12$rLutBKeV/k7TTF0TawCr/ekF5eDASjAu2VIXFdEFijoiuOAfGsBuG','Alger',0,'2026-03-19 02:53:55'),
(25,'Nadia','Lounis','nadia.lounis25@email.dz','0610416760','$2y$12$io/sl0.p8G1Nc8lywyx5w.ANYODZQSJZf8ZmbsxBAMKOYwQxr14Mu','Alger',0,'2026-02-22 03:55:09'),
(26,'Nesrine','Saidi','nesrine.saidi26@email.dz','0626832218','$2y$12$.fTGBYHXv3iNk25aEI2hoONAQrWs.4spNpsklW8OrdxFkf.JD.yIC','Alger',0,'2026-01-14 04:28:22'),
(27,'Lina','Rahmani','lina.rahmani27@email.dz','0679616681','$2y$12$DFO7zkH/bLm6G/j17rqezea4svCT.Y8DonufKBZt3wg8wDarwyDM2','Alger',0,'2026-04-24 21:07:34'),
(28,'Rania','Rahmani','rania.rahmani28@email.dz','0643851057','$2y$12$jwfvpyMMnZwbBsOBZw8g/eP676NRR/H49AZ.gNwYAYPydGKYmD/lC','Alger',0,'2026-07-27 06:42:25'),
(29,'Hana','Benali','hana.benali29@email.dz','0606500241','$2y$12$w4a5U/y6nm9ZX8.8sTeW2.hZa3me1snBQrVII9ThfuVlmuDfpW9uG','Alger',0,'2026-06-16 05:03:44'),
(30,'Meriem','Meziane','meriem.meziane30@email.dz','0564356177','$2y$12$zthd8PK.wdXnoFIuzsEtX.QzJiXD81MUXYdqNWG8PolMa9luWMcjq','Alger',0,'2026-03-19 04:16:31');

-- orders (25 lignes)
INSERT INTO `orders` (`id`,`reference`,`customer_id`,`customer_name`,`email`,`phone`,`subtotal`,`discount`,`total`,`payment_method`,`payment_ref`,`receipt_file`,`status`,`note`,`created_at`) VALUES
(1,'CMD-2026-0001',8,'Ines Toumi','ines.toumi8@email.dz','0641186247',1500,0,1500,'baridimob','','','pending','','2026-06-22 13:08:36'),
(2,'CMD-2026-0002',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',1500,0,1500,'baridimob','384224651','','paid','','2026-06-03 21:15:59'),
(3,'CMD-2026-0003',9,'Feriel Ferhat','feriel.ferhat9@email.dz','0509991721',2400,0,2400,'baridimob','211760988','','awaiting','','2026-07-02 14:58:01'),
(4,'CMD-2026-0004',25,'Nadia Lounis','nadia.lounis25@email.dz','0610416760',3800,0,3800,'baridimob','976313142','','paid','','2026-08-18 23:56:47'),
(5,'CMD-2026-0005',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',900,0,900,'baridimob','905929618','','paid','','2026-07-17 19:58:55'),
(6,'CMD-2026-0006',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',2700,0,2700,'baridimob','721281479','','paid','','2026-09-03 05:07:32'),
(7,'CMD-2026-0007',24,'Feriel Toumi','feriel.toumi24@email.dz','0695569495',3200,0,3200,'baridimob','866273529','','paid','','2026-06-19 06:01:06'),
(8,'CMD-2026-0008',8,'Ines Toumi','ines.toumi8@email.dz','0641186247',4400,0,4400,'baridimob','588748665','','awaiting','','2026-08-09 01:38:26'),
(9,'CMD-2026-0009',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',3700,0,3700,'baridimob','714324788','','cancelled','','2026-07-20 18:36:14'),
(10,'CMD-2026-0010',1,'Yasmine Demo','client@bibliotheque-numerique.dz','0671106498',3500,0,3500,'baridimob','961810826','','paid','','2026-07-14 07:34:53'),
(11,'CMD-2026-0011',26,'Nesrine Saidi','nesrine.saidi26@email.dz','0626832218',3500,0,3500,'baridimob','818112106','','paid','','2026-06-10 09:35:01'),
(12,'CMD-2026-0012',21,'Salima Haddad','salima.haddad21@email.dz','0686599006',1800,0,1800,'baridimob','297751897','','paid','','2026-06-26 16:21:19'),
(13,'CMD-2026-0013',1,'Yasmine Demo','client@bibliotheque-numerique.dz','0671106498',4200,0,4200,'baridimob','','','pending','','2026-08-07 02:37:06'),
(14,'CMD-2026-0014',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',900,0,900,'baridimob','996629564','','paid','','2026-08-23 22:32:19'),
(15,'CMD-2026-0015',2,'Salima Benali','salima.benali2@email.dz','0760750138',1200,0,1200,'baridimob','225236163','','awaiting','','2026-07-09 03:04:28'),
(16,'CMD-2026-0016',7,'Ines Saidi','ines.saidi7@email.dz','0537058832',3400,0,3400,'baridimob','542885123','','paid','','2026-08-21 12:35:30'),
(17,'CMD-2026-0017',12,'Katia Saidi','katia.saidi12@email.dz','0780025025',2400,0,2400,'baridimob','265330663','','paid','','2026-07-17 13:16:38'),
(18,'CMD-2026-0018',15,'Meriem Lounis','meriem.lounis15@email.dz','0741525932',900,0,900,'baridimob','390882781','','paid','','2026-07-14 12:30:03'),
(19,'CMD-2026-0019',27,'Lina Rahmani','lina.rahmani27@email.dz','0679616681',1800,0,1800,'baridimob','','','pending','','2026-08-29 18:03:27'),
(20,'CMD-2026-0020',21,'Salima Haddad','salima.haddad21@email.dz','0686599006',3700,0,3700,'baridimob','276768373','','paid','','2026-06-10 17:44:48'),
(21,'CMD-2026-0021',25,'Nadia Lounis','nadia.lounis25@email.dz','0610416760',1800,0,1800,'baridimob','','','pending','','2026-06-23 15:15:59'),
(22,'CMD-2026-0022',18,'Feriel Toumi','feriel.toumi18@email.dz','0684749559',2200,0,2200,'baridimob','144614117','','awaiting','','2026-09-02 21:18:29'),
(23,'CMD-2026-0023',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',4400,0,4400,'baridimob','798214567','','paid','','2026-07-25 16:44:48'),
(24,'CMD-2026-0024',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',2800,0,2800,'baridimob','430851034','','paid','','2026-07-24 20:29:43'),
(25,'CMD-2026-0025',22,'Hana Kaci','hana.kaci22@email.dz','0577981785',2900,0,2900,'baridimob','','','pending','','2026-07-09 03:00:19');

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
(1,3,3,2,'2026-06-03 21:15:59'),
(2,25,4,4,'2026-08-18 23:56:47'),
(3,25,5,4,'2026-08-18 23:56:47'),
(4,11,8,5,'2026-07-17 19:58:55'),
(5,11,2,6,'2026-09-03 05:07:32'),
(6,24,6,7,'2026-06-19 06:01:06'),
(7,24,10,7,'2026-06-19 06:01:06'),
(8,1,1,10,'2026-07-14 07:34:53'),
(9,1,3,10,'2026-07-14 07:34:53'),
(10,26,1,11,'2026-06-10 09:35:01'),
(11,26,3,11,'2026-06-10 09:35:01'),
(12,21,2,12,'2026-06-26 16:21:19'),
(13,3,8,14,'2026-08-23 22:32:19'),
(14,7,5,16,'2026-08-21 12:35:30'),
(15,7,6,16,'2026-08-21 12:35:30'),
(16,12,7,17,'2026-07-17 13:16:38'),
(17,15,8,18,'2026-07-14 12:30:03'),
(18,21,1,20,'2026-06-10 17:44:48'),
(19,21,9,20,'2026-06-10 17:44:48'),
(20,11,1,23,'2026-07-25 16:44:48'),
(21,11,7,23,'2026-07-25 16:44:48'),
(22,3,4,24,'2026-07-24 20:29:43'),
(23,3,6,24,'2026-07-24 20:29:43');

-- coupons (5 lignes)
INSERT INTO `coupons` (`id`,`code`,`type`,`value`,`min_amount`,`description`,`expires_at`,`usage_limit`,`used`,`active`) VALUES
(1,'BIENVENUE10','percent',10,0,'-10% première commande','2026-10-04',93,29,1),
(2,'LECTURE15','percent',15,3000,'-15% dès 3000 DA','2026-12-27',54,10,1),
(3,'LECTEUR500','fixed',500,2000,'-500 DA','2026-10-04',146,28,1),
(4,'RAMADAN20','percent',20,4000,'Offre Ramadan -20%','2026-11-08',139,2,1),
(5,'GRATUIT','fixed',300,900,'-300 DA','2026-10-14',142,9,1);

-- blog_posts (8 lignes)
INSERT INTO `blog_posts` (`id`,`title`,`slug`,`category`,`excerpt`,`body`,`image`,`author`,`tags`,`status`,`views`,`published_at`) VALUES
(1,'Comment lire plus régulièrement','comment-lire-plus-regulierement','Conseils','Mes conseils pour comment lire plus régulièrement.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',249,'2026-04-19 08:27:59'),
(2,'5 livres pour bien démarrer une activité','5-livres-pour-bien-demarrer-une-activite','Conseils','Mes conseils pour 5 livres pour bien démarrer une activité.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',3176,'2026-05-22 18:40:17'),
(3,'Construire une présentation qui convainc','construire-une-presentation-qui-convainc','Tutoriels','Mes conseils pour construire une présentation qui convainc.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',2977,'2026-07-27 07:05:22'),
(4,'Prendre des notes efficacement','prendre-des-notes-efficacement','Tutoriels','Mes conseils pour prendre des notes efficacement.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',1097,'2026-05-24 14:51:39'),
(5,'Organiser sa bibliothèque numérique','organiser-sa-bibliotheque-numerique','Conseils','Mes conseils pour organiser sa bibliothèque numérique.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',3024,'2026-05-24 10:50:25'),
(6,'Les erreurs classiques d''un diaporama','les-erreurs-classiques-d-un-diaporama','Tutoriels','Mes conseils pour les erreurs classiques d''un diaporama.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',2156,'2026-06-10 09:23:20'),
(7,'PDF ou PowerPoint : quel format choisir','pdf-ou-powerpoint-quel-format-choisir','Tutoriels','Mes conseils pour pdf ou powerpoint : quel format choisir.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',346,'2026-06-19 17:35:05'),
(8,'Se former en ligne sans se disperser','se-former-en-ligne-sans-se-disperser','Business','Mes conseils pour se former en ligne sans se disperser.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Business, lecture, conseils','published',1107,'2026-06-15 07:39:22');

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
