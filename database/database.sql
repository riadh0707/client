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
('La Bibliothèque','admin@bibliotheque-numerique.dz','$2y$12$lRqpHkZVfxSn1izyIePD1uuLHc3FqASidmw7qoZ44yaah58BcCBz.','super');

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
(1,1,NULL,'Amina B.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-06-08 20:55:10'),
(2,1,NULL,'Feriel D.',4,'Coup de cœur !','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-03-23 19:01:12'),
(3,1,NULL,'Katia S.',4,'Excellent','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-03-13 00:02:56'),
(4,1,NULL,'Amina B.',4,'Excellent','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-06-28 11:52:27'),
(5,1,NULL,'Katia S.',5,'Coup de cœur !','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-08-04 18:59:42'),
(6,2,NULL,'Feriel D.',4,'Excellent','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-03-23 23:09:40'),
(7,2,NULL,'Nesrine T.',5,'Coup de cœur !','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-03-12 18:02:18'),
(8,3,NULL,'Lydia K.',5,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-09-03 02:55:22'),
(9,3,NULL,'Yasmine H.',4,'Parfait pour débuter','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-05-17 08:00:39'),
(10,3,NULL,'Feriel D.',4,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-03-19 23:22:01'),
(11,4,NULL,'Lydia K.',4,'Je recommande','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-08-26 00:28:43'),
(12,4,NULL,'Nesrine T.',5,'Excellent','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-05 09:04:20'),
(13,4,NULL,'Nesrine T.',4,'Parfait pour débuter','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-07-12 00:56:45'),
(14,5,NULL,'Yasmine H.',4,'Coup de cœur !','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-07-31 15:18:33'),
(15,5,NULL,'Lydia K.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-05-22 02:14:13'),
(16,5,NULL,'Rania L.',5,'Excellent','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-08-27 13:08:45'),
(17,6,NULL,'Katia S.',4,'Je recommande','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-05-22 12:21:55'),
(18,6,NULL,'Feriel D.',4,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','pending','2026-04-19 21:41:16'),
(19,7,NULL,'Sonia G.',5,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-07-01 04:06:06'),
(20,7,NULL,'Rania L.',5,'Excellent','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-08-07 16:01:36'),
(21,7,NULL,'Sonia G.',4,'Parfait pour débuter','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-05-20 17:00:30'),
(22,7,NULL,'Katia S.',4,'Très complet','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-08-19 20:30:58'),
(23,7,NULL,'Amina B.',4,'Très complet','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-07-27 04:46:00'),
(24,8,NULL,'Feriel D.',5,'Parfait pour débuter','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','approved','2026-06-06 08:33:53'),
(25,8,NULL,'Katia S.',4,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-08-20 17:25:47'),
(26,8,NULL,'Lydia K.',5,'Je recommande','Contenu magnifique, les explications sont claires et directement applicables. J''ai déjà mis en pratique !','approved','2026-05-05 21:21:31'),
(27,8,NULL,'Katia S.',4,'Très complet','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-25 09:42:59'),
(28,8,NULL,'Yasmine H.',5,'Parfait pour débuter','Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.','approved','2026-05-29 02:12:26'),
(29,9,NULL,'Amina B.',4,'Excellent','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','pending','2026-05-01 04:27:08'),
(30,9,NULL,'Yasmine H.',4,'Coup de cœur !','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-06-27 02:33:33'),
(31,10,NULL,'Nesrine T.',4,'Très complet','Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.','approved','2026-05-06 06:27:23'),
(32,10,NULL,'Feriel D.',5,'Parfait pour débuter','Aperçu très utile avant d''acheter, et le contenu complet vaut largement le prix. Merci !','approved','2026-06-05 23:48:44'),
(33,10,NULL,'Rania L.',4,'Parfait pour débuter','Un vrai trésor de conseils. Le chapitre sur la méthode m''a beaucoup aidé.','pending','2026-06-29 06:23:07');

-- customers (30 lignes)
INSERT INTO `customers` (`id`,`first_name`,`last_name`,`email`,`phone`,`password`,`wilaya`,`loyalty_points`,`created_at`) VALUES
(1,'Yasmine','Demo','client@bibliotheque-numerique.dz','0671106498','$2y$12$5nZGC9kbdixwb/oGenuMz.NI.6w0g5DR1GhZU6p.Qkek1GdeNtXzO','Alger',0,'2026-07-06 03:52:57'),
(2,'Salima','Benali','salima.benali2@email.dz','0760750138','$2y$12$oSV3Q/tYn5LTSmPohuYIju3TcppqNpfbuKnmCDQT9Yqpr53iMPNsC','Alger',0,'2026-08-02 20:54:24'),
(3,'Sarah','Cherif','sarah.cherif3@email.dz','0504232163','$2y$12$qMRluj0FSmZMMEsabGbn..XAs/G1OpHuvl5itLJmZzdoTppgRilMS','Alger',0,'2026-03-10 05:52:06'),
(4,'Hana','Toumi','hana.toumi4@email.dz','0764817267','$2y$12$Sh2ayMNX0aqidZyxn5d3guOmlumeZT1Kgol88esA7gYuaGhkcvmDS','Alger',0,'2026-01-02 04:41:29'),
(5,'Feriel','Gacem','feriel.gacem5@email.dz','0569071363','$2y$12$1QbMDj82DMJ012E6pQXEneckjBQ.wEO/Cl0NBfuW77SLWBMrHe.7u','Alger',0,'2026-01-10 19:41:48'),
(6,'Amina','Haddad','amina.haddad6@email.dz','0700222760','$2y$12$LtFJDR/WOb50AKHJhqXi.um6nh.eFwN35zy3R0W5TXhE7i5Wn0GKm','Alger',0,'2026-05-13 14:42:27'),
(7,'Ines','Saidi','ines.saidi7@email.dz','0537058832','$2y$12$ietZAmj8lFKNpv4hSwotguDPKcia6aI4UDDbv3sUvEixoBTX7wZOe','Alger',0,'2026-03-10 17:35:16'),
(8,'Ines','Toumi','ines.toumi8@email.dz','0641186247','$2y$12$Oyd8mntAusRQRVP7QsyTc.6nqOGX9CYH6q6TOcWeQ2nwclqiHIdiu','Alger',0,'2026-07-24 05:19:02'),
(9,'Feriel','Ferhat','feriel.ferhat9@email.dz','0509991721','$2y$12$PKhk6KllNy51NnQL3SRnSOtZYjps8.Ie9je6bGVVygnbYQoaQwdY.','Alger',0,'2026-05-21 13:04:57'),
(10,'Rania','Kaci','rania.kaci10@email.dz','0512725464','$2y$12$sdfsd2u47v.PXHA65iYXGeVlGtVt4LWYsROu7BrFGvARi07.zSdV2','Alger',0,'2026-03-30 19:40:53'),
(11,'Ines','Kaci','ines.kaci11@email.dz','0685316961','$2y$12$HvwNjxiA7WEDOeYf3roy2eT1O5AJitkgGM/Y00eIUaN3StH8.1VrC','Alger',0,'2026-05-03 07:06:19'),
(12,'Katia','Saidi','katia.saidi12@email.dz','0780025025','$2y$12$l7fA4Q9L27Im/7W9APF9NuqYfni7aiqG/6B8EAd6pj0w6mbPR5.Fq','Alger',0,'2026-05-31 18:20:12'),
(13,'Feriel','Meziane','feriel.meziane13@email.dz','0747219824','$2y$12$SX6nLZH8dBcDl.ROr8zXDulBHOG1UoSy5ptVrCOuSkMiEObB4RpUm','Alger',0,'2026-06-06 10:02:07'),
(14,'Hana','Lounis','hana.lounis14@email.dz','0720134766','$2y$12$/hKCF0dp.mcO79oStkO3WeFO6Rnl8OjFAshl2RsGDlmiMX2AyX6na','Alger',0,'2026-03-17 16:16:18'),
(15,'Meriem','Lounis','meriem.lounis15@email.dz','0741525932','$2y$12$5Cyd.YB5xKUq2KTJs7/7feA.Wql8h3tEZMJRbEWRFUluDEFM2BwHi','Alger',0,'2026-07-05 07:27:08'),
(16,'Salima','Ferhat','salima.ferhat16@email.dz','0785280825','$2y$12$RaIvWtiNf82ScobglXC03uJdnJkicHsriMHoOxa/dIeWVFEWcRZF.','Alger',0,'2026-06-23 09:29:55'),
(17,'Lina','Rahmani','lina.rahmani17@email.dz','0507206626','$2y$12$KrcKzJpe4r6lLd2Rnm0IXubVr6bCd4eHCJRnL7aZ.9tM9H9Ra6PZu','Alger',0,'2026-01-01 19:19:03'),
(18,'Feriel','Toumi','feriel.toumi18@email.dz','0684749559','$2y$12$0pjvwfJ1pXWRdEAL0NKz/eMEVwBNeCZKb3.rXeiH0NKnn4vydLkZW','Alger',0,'2026-05-02 06:46:13'),
(19,'Ines','Cherif','ines.cherif19@email.dz','0647173112','$2y$12$.v5OlPg091H2IqJ.2UO.TOxSdU0wBve4AVGP9e9LKaMoOIu9KYQkK','Alger',0,'2026-03-12 03:33:50'),
(20,'Rania','Meziane','rania.meziane20@email.dz','0762488271','$2y$12$aA9LRB3DG.alx88SxqRhU.B3yiclQilVEtH9TkC6MculIfCZBAqAC','Alger',0,'2026-03-12 09:00:03'),
(21,'Salima','Haddad','salima.haddad21@email.dz','0686599006','$2y$12$zNAZ3TN6/WOBJ3n3A3ocRO7r3ynmJB/2.djXrmK/2xYwKqMNGhJ3q','Alger',0,'2026-02-23 16:06:26'),
(22,'Hana','Kaci','hana.kaci22@email.dz','0577981785','$2y$12$gNLl601ay644VzdAZeVOi.n3GvF/yTdvV4uNEbHF6xouvX8NDyIBy','Alger',0,'2026-08-08 19:02:57'),
(23,'Dalia','Kaci','dalia.kaci23@email.dz','0702975434','$2y$12$Aw0yhgBO/V5amfBbpoKZi.hHmMbDG/AuDtYEfv55x26B7Qq5Mtzlq','Alger',0,'2026-01-16 07:59:36'),
(24,'Feriel','Toumi','feriel.toumi24@email.dz','0695569495','$2y$12$P5KveJDZBdgH5WQPdvfRLeYaDyIY62AigwYgjdpkPCSaXWQrPzCre','Alger',0,'2026-03-19 03:30:44'),
(25,'Nadia','Lounis','nadia.lounis25@email.dz','0610416760','$2y$12$hdWFIFjZqVBy.3UXyQW37uJtuA1jswsgDhyIxTqZBsP58h3vwoGpa','Alger',0,'2026-02-22 04:31:58'),
(26,'Nesrine','Saidi','nesrine.saidi26@email.dz','0626832218','$2y$12$C2aTmQxd2B6xnpk6BZHsYOC6WvuG0He7vpvR7O5EuG9jBHhNppG.W','Alger',0,'2026-01-14 05:05:12'),
(27,'Lina','Rahmani','lina.rahmani27@email.dz','0679616681','$2y$12$LFDPFkHCWqBoIomuayTYOOnBMoXyYwzmltG9YUjrGP1cnXf8TLX4a','Alger',0,'2026-04-24 21:44:23'),
(28,'Rania','Rahmani','rania.rahmani28@email.dz','0643851057','$2y$12$7aDYcFRRN0LxV2NIn4c9RuStmZHD1PAWrbjcosUQ7DLczhXPRc42.','Alger',0,'2026-07-27 07:19:14'),
(29,'Hana','Benali','hana.benali29@email.dz','0606500241','$2y$12$2Pp7stS/VnKgr80CCdTxI.lLabmALea2ai6heSjWmwc9sj/ECul4S','Alger',0,'2026-06-16 05:40:33'),
(30,'Meriem','Meziane','meriem.meziane30@email.dz','0564356177','$2y$12$sgrt/XfoHI62af47BIeAHuTj..E9Ik51HdOPvfVfVB/.qOfSb.gTS','Alger',0,'2026-03-19 04:53:21');

-- orders (25 lignes)
INSERT INTO `orders` (`id`,`reference`,`customer_id`,`customer_name`,`email`,`phone`,`subtotal`,`discount`,`total`,`payment_method`,`payment_ref`,`receipt_file`,`status`,`note`,`created_at`) VALUES
(1,'CMD-2026-0001',8,'Ines Toumi','ines.toumi8@email.dz','0641186247',1500,0,1500,'baridimob','','','pending','','2026-06-22 13:45:26'),
(2,'CMD-2026-0002',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',1500,0,1500,'baridimob','384224651','','paid','','2026-06-03 21:52:49'),
(3,'CMD-2026-0003',9,'Feriel Ferhat','feriel.ferhat9@email.dz','0509991721',2400,0,2400,'baridimob','211760988','','awaiting','','2026-07-02 15:34:51'),
(4,'CMD-2026-0004',25,'Nadia Lounis','nadia.lounis25@email.dz','0610416760',3800,0,3800,'baridimob','976313142','','paid','','2026-08-19 00:33:37'),
(5,'CMD-2026-0005',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',900,0,900,'baridimob','905929618','','paid','','2026-07-17 20:35:45'),
(6,'CMD-2026-0006',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',2700,0,2700,'baridimob','721281479','','paid','','2026-09-03 05:44:22'),
(7,'CMD-2026-0007',24,'Feriel Toumi','feriel.toumi24@email.dz','0695569495',3200,0,3200,'baridimob','866273529','','paid','','2026-06-19 06:37:56'),
(8,'CMD-2026-0008',8,'Ines Toumi','ines.toumi8@email.dz','0641186247',4400,0,4400,'baridimob','588748665','','awaiting','','2026-08-09 02:15:16'),
(9,'CMD-2026-0009',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',3700,0,3700,'baridimob','714324788','','cancelled','','2026-07-20 19:13:04'),
(10,'CMD-2026-0010',1,'Yasmine Demo','client@bibliotheque-numerique.dz','0671106498',3500,0,3500,'baridimob','961810826','','paid','','2026-07-14 08:11:43'),
(11,'CMD-2026-0011',26,'Nesrine Saidi','nesrine.saidi26@email.dz','0626832218',3500,0,3500,'baridimob','818112106','','paid','','2026-06-10 10:11:51'),
(12,'CMD-2026-0012',21,'Salima Haddad','salima.haddad21@email.dz','0686599006',1800,0,1800,'baridimob','297751897','','paid','','2026-06-26 16:58:09'),
(13,'CMD-2026-0013',1,'Yasmine Demo','client@bibliotheque-numerique.dz','0671106498',4200,0,4200,'baridimob','','','pending','','2026-08-07 03:13:56'),
(14,'CMD-2026-0014',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',900,0,900,'baridimob','996629564','','paid','','2026-08-23 23:09:09'),
(15,'CMD-2026-0015',2,'Salima Benali','salima.benali2@email.dz','0760750138',1200,0,1200,'baridimob','225236163','','awaiting','','2026-07-09 03:41:18'),
(16,'CMD-2026-0016',7,'Ines Saidi','ines.saidi7@email.dz','0537058832',3400,0,3400,'baridimob','542885123','','paid','','2026-08-21 13:12:20'),
(17,'CMD-2026-0017',12,'Katia Saidi','katia.saidi12@email.dz','0780025025',2400,0,2400,'baridimob','265330663','','paid','','2026-07-17 13:53:28'),
(18,'CMD-2026-0018',15,'Meriem Lounis','meriem.lounis15@email.dz','0741525932',900,0,900,'baridimob','390882781','','paid','','2026-07-14 13:06:53'),
(19,'CMD-2026-0019',27,'Lina Rahmani','lina.rahmani27@email.dz','0679616681',1800,0,1800,'baridimob','','','pending','','2026-08-29 18:40:17'),
(20,'CMD-2026-0020',21,'Salima Haddad','salima.haddad21@email.dz','0686599006',3700,0,3700,'baridimob','276768373','','paid','','2026-06-10 18:21:38'),
(21,'CMD-2026-0021',25,'Nadia Lounis','nadia.lounis25@email.dz','0610416760',1800,0,1800,'baridimob','','','pending','','2026-06-23 15:52:49'),
(22,'CMD-2026-0022',18,'Feriel Toumi','feriel.toumi18@email.dz','0684749559',2200,0,2200,'baridimob','144614117','','awaiting','','2026-09-02 21:55:19'),
(23,'CMD-2026-0023',11,'Ines Kaci','ines.kaci11@email.dz','0685316961',4400,0,4400,'baridimob','798214567','','paid','','2026-07-25 17:21:38'),
(24,'CMD-2026-0024',3,'Sarah Cherif','sarah.cherif3@email.dz','0504232163',2800,0,2800,'baridimob','430851034','','paid','','2026-07-24 21:06:33'),
(25,'CMD-2026-0025',22,'Hana Kaci','hana.kaci22@email.dz','0577981785',2900,0,2900,'baridimob','','','pending','','2026-07-09 03:37:09');

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
(1,3,3,2,'2026-06-03 21:52:49'),
(2,25,4,4,'2026-08-19 00:33:37'),
(3,25,5,4,'2026-08-19 00:33:37'),
(4,11,8,5,'2026-07-17 20:35:45'),
(5,11,2,6,'2026-09-03 05:44:22'),
(6,24,6,7,'2026-06-19 06:37:56'),
(7,24,10,7,'2026-06-19 06:37:56'),
(8,1,1,10,'2026-07-14 08:11:43'),
(9,1,3,10,'2026-07-14 08:11:43'),
(10,26,1,11,'2026-06-10 10:11:51'),
(11,26,3,11,'2026-06-10 10:11:51'),
(12,21,2,12,'2026-06-26 16:58:09'),
(13,3,8,14,'2026-08-23 23:09:09'),
(14,7,5,16,'2026-08-21 13:12:20'),
(15,7,6,16,'2026-08-21 13:12:20'),
(16,12,7,17,'2026-07-17 13:53:28'),
(17,15,8,18,'2026-07-14 13:06:53'),
(18,21,1,20,'2026-06-10 18:21:38'),
(19,21,9,20,'2026-06-10 18:21:38'),
(20,11,1,23,'2026-07-25 17:21:38'),
(21,11,7,23,'2026-07-25 17:21:38'),
(22,3,4,24,'2026-07-24 21:06:33'),
(23,3,6,24,'2026-07-24 21:06:33');

-- coupons (5 lignes)
INSERT INTO `coupons` (`id`,`code`,`type`,`value`,`min_amount`,`description`,`expires_at`,`usage_limit`,`used`,`active`) VALUES
(1,'BIENVENUE10','percent',10,0,'-10% première commande','2026-10-04',93,29,1),
(2,'LECTURE15','percent',15,3000,'-15% dès 3000 DA','2026-12-27',54,10,1),
(3,'LECTEUR500','fixed',500,2000,'-500 DA','2026-10-04',146,28,1),
(4,'RAMADAN20','percent',20,4000,'Offre Ramadan -20%','2026-11-08',139,2,1),
(5,'GRATUIT','fixed',300,900,'-300 DA','2026-10-14',142,9,1);

-- blog_posts (8 lignes)
INSERT INTO `blog_posts` (`id`,`title`,`slug`,`category`,`excerpt`,`body`,`image`,`author`,`tags`,`status`,`views`,`published_at`) VALUES
(1,'Comment lire plus régulièrement','comment-lire-plus-regulierement','Conseils','Mes conseils pour comment lire plus régulièrement.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',249,'2026-04-19 09:04:49'),
(2,'5 livres pour bien démarrer une activité','5-livres-pour-bien-demarrer-une-activite','Conseils','Mes conseils pour 5 livres pour bien démarrer une activité.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',3176,'2026-05-22 19:17:07'),
(3,'Construire une présentation qui convainc','construire-une-presentation-qui-convainc','Tutoriels','Mes conseils pour construire une présentation qui convainc.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',2977,'2026-07-27 07:42:12'),
(4,'Prendre des notes efficacement','prendre-des-notes-efficacement','Tutoriels','Mes conseils pour prendre des notes efficacement.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',1097,'2026-05-24 15:28:29'),
(5,'Organiser sa bibliothèque numérique','organiser-sa-bibliotheque-numerique','Conseils','Mes conseils pour organiser sa bibliothèque numérique.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Conseils, lecture, conseils','published',3024,'2026-05-24 11:27:15'),
(6,'Les erreurs classiques d''un diaporama','les-erreurs-classiques-d-un-diaporama','Tutoriels','Mes conseils pour les erreurs classiques d''un diaporama.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',2156,'2026-06-10 10:00:10'),
(7,'PDF ou PowerPoint : quel format choisir','pdf-ou-powerpoint-quel-format-choisir','Tutoriels','Mes conseils pour pdf ou powerpoint : quel format choisir.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Tutoriels, lecture, conseils','published',346,'2026-06-19 18:11:55'),
(8,'Se former en ligne sans se disperser','se-former-en-ligne-sans-se-disperser','Business','Mes conseils pour se former en ligne sans se disperser.','<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l''organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l''emploi.</p>',NULL,'La Bibliothèque','Business, lecture, conseils','published',1107,'2026-06-15 08:16:12');

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
