<?php
/**
 * data.php — Source unique des données de démonstration (déterministe).
 * Bibliothèque de livres et présentations numériques : livres, catégories, avis,
 * clients, commandes, coupons, blog, témoignages, paramètres.
 *
 * NB : pour la démo, tous les livres pointent vers le PDF réel fourni
 * (uploads/pdf/livre-exemple.pdf) afin que l'aperçu et la lecture
 * fonctionnent. L'administrateur remplacera chaque fichier par le sien depuis l'admin.
 */

function demo_data(): array
{
    mt_srand(20240811);

    $SAMPLE_PDF     = 'livre-exemple.pdf';
    $SAMPLE_PREVIEW = 'livre-exemple.pdf';

    /* ---------------- Paramètres du site ---------------- */
    $settings = [
        'site_name'         => 'La Bibliothèque Numérique',
        'site_name_ar'      => 'المكتبة الرقمية',
        'site_tagline'      => 'Des livres et des présentations, partout avec vous.',
        'meta_description'  => 'Livres et présentations numériques (PDF et PowerPoint) : culture, formation, développement personnel et savoir-faire. Aperçu gratuit, achat sécurisé par BaridiMob, lecture en ligne immédiate.',
        'contact_email'     => 'contact@bibliotheque-numerique.dz',
        'contact_phone'     => '+213 550 12 34 56',
        'contact_address'   => 'Alger, Algérie',
        'default_preview_pages' => '10',
        'baridimob_rip'     => '007 9999 0001 2345 6789',
        'baridimob_name'    => 'LA BIBLIOTHEQUE NUMERIQUE',
        'baridimob_note'    => 'Après votre virement BaridiMob, indiquez la référence de transaction. Notre équipe validera votre accès sous 24h.',
        'social_instagram'  => 'https://instagram.com/bibliotheque.numerique',
        'social_facebook'   => 'https://facebook.com/bibliotheque.numerique',
        'social_tiktok'     => 'https://tiktok.com/@bibliotheque.numerique',
        'announcement'      => 'Aperçu gratuit de chaque titre • Livres PDF & présentations PowerPoint • Lecture en ligne immédiate',
    ];

    /* ---------------- Admin (notre équipe) ---------------- */
    $admins = [[
        'name'     => 'La Bibliothèque',
        'email'    => 'admin@bibliotheque-numerique.dz',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role'     => 'super',
    ]];

    /* ---------------- Catégories ---------------- */
    $catNames = [
        ['Business & Entrepreneuriat', 'wallet'],
        ['Développement personnel', 'sparkles'],
        ['Informatique & Numérique', 'grid'],
        ['Langues & Communication', 'chat'],
        ['Sciences & Techniques', 'award'],
        ['Histoire & Culture', 'book'],
        ['Présentations & Formations', 'presentation'],
    ];
    $categories = [];
    foreach ($catNames as $i => $c) {
        $categories[] = [
            'id' => $i + 1, 'parent_id' => null, 'name' => $c[0], 'slug' => slugify($c[0]),
            'icon' => $c[1], 'description' => 'Nos livres — ' . $c[0] . '.',
            'seo_title' => $c[0] . ' | La Bibliothèque Numérique', 'position' => $i + 1,
        ];
    }

    /* ---------------- Livres ---------------- */
    // [titre, auteur, cat_id, prix, ancien_prix, pages, aperçu, featured, new, best]
    // Le format ('pdf' ou 'pptx') détermine le lecteur utilisé en ligne ;
    // le 12e champ nomme le fichier de démonstration (null = le PDF exemple).
    // Les présentations de démo comptent réellement 7 et 6 diapositives.
    $bookDefs = [
        ['Mon projet commence aujourd\'hui', 'Amine Belkacem', 1, 2000, null, 20, 10, 1, 0, 1, 'pdf', null],
        ['Bien démarrer son activité en ligne', 'Karim Ammar', 1, 1800, 2500, 20, 8, 1, 0, 0, 'pdf', null],
        ['Le grand livre de la productivité', 'Sonia Gacem', 2, 1500, null, 20, 6, 0, 1, 0, 'pdf', null],
        ['Prendre la parole en public', 'Yacine Toumi', 4, 1600, null, 20, 10, 0, 1, 0, 'pdf', null],
        ['Histoire et patrimoine algériens', 'Meriem Saidi', 6, 2200, null, 20, 12, 1, 0, 1, 'pdf', null],
        ['Excel & tableurs : le guide express', 'Karim Ammar', 3, 1200, 1600, 20, 5, 0, 1, 0, 'pdf', null],
        ["L'art de la présentation efficace", 'Lydia Kaci', 7, 2400, null, 7, 3, 0, 0, 1, 'pptx', 'presentation-exemple.pptx'],
        ['Anglais professionnel en 30 jours', 'Sarah Meziane', 4, 900, 1300, 20, 6, 0, 1, 0, 'pdf', null],
        ['Introduction aux sciences des données', 'Feriel Cherif', 5, 1700, null, 20, 10, 1, 0, 0, 'pdf', null],
        ['Formation : gérer une équipe', 'Rania Lounis', 7, 2000, null, 6, 2, 1, 0, 1, 'pptx', 'formation-equipe-exemple.pptx'],
    ];
    $descLong = "Un titre pensé pour vous accompagner pas à pas, avec des explications claires, illustrées et vérifiées. Chaque chapitre vous guide des bases jusqu'à la mise en pratique, avec des conseils de professionnels et des fiches à réutiliser. Lisible en ligne depuis votre ordinateur, votre tablette ou votre téléphone.";
    $tocSample = "Introduction — Pourquoi ce titre\nChapitre 1 : Les fondamentaux\nChapitre 2 : Bien démarrer\nChapitre 3 : La méthode pas à pas\nChapitre 4 : Outils et modèles\nChapitre 5 : Aller plus loin\nAnnexes & fiches pratiques";

    $books = [];
    $reviews = [];
    $rid = 0;
    $reviewAuthors = ['Amina B.', 'Lydia K.', 'Sarah M.', 'Nesrine T.', 'Yasmine H.', 'Feriel D.', 'Katia S.', 'Rania L.', 'Meriem A.', 'Sonia G.'];
    $reviewBodies = [
        'Contenu magnifique, les explications sont claires et directement applicables. J\'ai déjà mis en pratique !',
        'Aperçu très utile avant d\'acheter, et le contenu complet vaut largement le prix. Merci !',
        'Les explications sont détaillées et les illustrations très claires. Je recommande à 100%.',
        'Parfait pour débuter. La lecture en ligne est pratique, je le consulte depuis mon téléphone.',
        'Un vrai trésor de conseils. Le chapitre sur la méthode m\'a beaucoup aidé.',
    ];
    $reviewTitles = ['Coup de cœur !', 'Je recommande', 'Très complet', 'Parfait pour débuter', 'Excellent'];

    foreach ($bookDefs as $i => $d) {
        $id = $i + 1;
        [$title, $author, $cat, $price, $old, $pages, $preview, $feat, $new, $best, $fileType, $sampleFile] = $d;
        $rating = round(mt_rand(43, 50) / 10, 1);
        $books[] = [
            'id'            => $id,
            'title'         => $title,
            'slug'          => slugify($title),
            'author'        => $author,
            'sku'           => 'LIV-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT),
            'category_id'   => $cat,
            'short_desc'    => ($fileType === 'pptx' ? 'Une présentation' : 'Un livre') . ' signé ' . $author . ' — aperçu gratuit disponible.',
            'long_desc'     => $descLong,
            'toc'           => $tocSample,
            'price'         => $price,
            'old_price'     => $old,
            'on_sale'       => $old ? 1 : 0,
            'pages_count'   => $pages,
            'preview_pages' => $preview,
            'language'      => 'Français',
            'file_type'     => $fileType,
            'pdf_file'      => $sampleFile ?: $SAMPLE_PDF,
            'preview_file'  => $fileType === 'pptx' ? null : $SAMPLE_PREVIEW,
            'cover_image'   => $id === 1 ? 'livre-exemple-cover.png' : null,
            'is_featured'   => $feat,
            'is_new'        => $new,
            'is_bestseller' => $best,
            'rating'        => $rating,
            'reviews_count' => mt_rand(6, 90),
            'status'        => 'active',
        ];
        // 2 à 5 avis publiés par livre
        for ($k = 0, $nn = mt_rand(2, 5); $k < $nn; $k++) {
            $rid++;
            $reviews[] = [
                'id' => $rid, 'book_id' => $id, 'customer_id' => null,
                'author_name' => $reviewAuthors[mt_rand(0, count($reviewAuthors) - 1)],
                'rating' => mt_rand(4, 5),
                'title' => $reviewTitles[mt_rand(0, count($reviewTitles) - 1)],
                'body' => $reviewBodies[mt_rand(0, count($reviewBodies) - 1)],
                'status' => mt_rand(1, 100) <= 90 ? 'approved' : 'pending',
                'created_at' => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 180)),
            ];
        }
    }

    /* ---------------- Clients ---------------- */
    $firstNames = ['Amina', 'Lydia', 'Sarah', 'Nesrine', 'Yasmine', 'Feriel', 'Katia', 'Rania', 'Meriem', 'Sonia', 'Ines', 'Dalia', 'Nadia', 'Salima', 'Hana', 'Lina'];
    $lastNames  = ['Benali', 'Kaci', 'Meziane', 'Toumi', 'Haddad', 'Cherif', 'Saidi', 'Lounis', 'Ammar', 'Gacem', 'Rahmani', 'Ferhat'];
    $customers = [];
    for ($i = 1; $i <= 30; $i++) {
        $fn = $firstNames[mt_rand(0, count($firstNames) - 1)];
        $ln = $lastNames[mt_rand(0, count($lastNames) - 1)];
        $customers[] = [
            'id' => $i, 'first_name' => $fn, 'last_name' => $ln,
            'email' => strtolower($fn . '.' . $ln . $i) . '@email.dz',
            'phone' => '0' . mt_rand(5, 7) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => password_hash('client123', PASSWORD_DEFAULT),
            'wilaya' => 'Alger', 'loyalty_points' => 0,
            'created_at' => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 260)),
        ];
    }
    $customers[0]['email'] = 'client@bibliotheque-numerique.dz';
    $customers[0]['first_name'] = 'Yasmine';
    $customers[0]['last_name'] = 'Demo';

    /* ---------------- Commandes + accès livres ---------------- */
    $statuses = ['pending' => 10, 'awaiting' => 15, 'paid' => 70, 'cancelled' => 5];
    $orders = []; $orderItems = []; $access = [];
    $oi = 0; $ac = 0; $accessSeen = [];
    for ($i = 1; $i <= 25; $i++) {
        $cust = $customers[mt_rand(0, count($customers) - 1)];
        $num = mt_rand(1, 2);
        $picked = (array) array_rand($books, min($num, count($books)));
        $subtotal = 0; $itemsFor = [];
        foreach ($picked as $bi) {
            $b = $books[$bi];
            $subtotal += $b['price'];
            $oi++;
            $itemsFor[] = ['id' => $oi, 'order_id' => $i, 'book_id' => $b['id'], 'title' => $b['title'], 'price' => $b['price']];
        }
        // statut pondéré
        $r = mt_rand(1, 100); $acc = 0; $status = 'paid';
        foreach ($statuses as $s => $w) { $acc += $w; if ($r <= $acc) { $status = $s; break; } }

        $orders[] = [
            'id' => $i, 'reference' => 'CMD-' . date('Y') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'customer_id' => $cust['id'], 'customer_name' => $cust['first_name'] . ' ' . $cust['last_name'],
            'email' => $cust['email'], 'phone' => $cust['phone'],
            'subtotal' => $subtotal, 'discount' => 0, 'total' => $subtotal,
            'payment_method' => 'baridimob',
            'payment_ref' => $status === 'pending' ? '' : (string) mt_rand(100000000, 999999999),
            'receipt_file' => '', 'status' => $status, 'note' => '',
            'created_at' => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 100)),
        ];
        foreach ($itemsFor as $it) {
            $orderItems[] = $it;
            $key = $cust['id'] . '-' . $it['book_id'];
            if ($status === 'paid' && !isset($accessSeen[$key])) {
                $accessSeen[$key] = true;
                $ac++;
                $access[] = ['id' => $ac, 'customer_id' => $cust['id'], 'book_id' => $it['book_id'], 'order_id' => $i,
                    'created_at' => $orders[$i - 1]['created_at']];
            }
        }
    }
    // Le client démo possède le livre principal pour montrer la lecture complète.
    if (!isset($accessSeen['1-1'])) {
        $ac++;
        $access[] = ['id' => $ac, 'customer_id' => 1, 'book_id' => 1, 'order_id' => null, 'created_at' => date('Y-m-d H:i:s')];
    }

    /* ---------------- Coupons ---------------- */
    $coupons = [
        ['BIENVENUE10', 'percent', 10, 0, '-10% première commande'],
        ['LECTURE15', 'percent', 15, 3000, '-15% dès 3000 DA'],
        ['LECTEUR500', 'fixed', 500, 2000, '-500 DA'],
        ['RAMADAN20', 'percent', 20, 4000, 'Offre Ramadan -20%'],
        ['GRATUIT', 'fixed', 300, 900, '-300 DA'],
    ];
    $couponRows = [];
    foreach ($coupons as $i => $c) {
        $couponRows[] = ['id' => $i + 1, 'code' => $c[0], 'type' => $c[1], 'value' => $c[2], 'min_amount' => $c[3],
            'desc' => $c[4], 'expires_at' => date('Y-m-d', time() + 86400 * mt_rand(30, 120)),
            'usage_limit' => mt_rand(50, 300), 'used' => mt_rand(0, 30), 'active' => 1];
    }

    /* ---------------- Blog ---------------- */
    $blogTopics = [
        ['Comment lire plus régulièrement', 'Conseils'],
        ['5 livres pour bien démarrer une activité', 'Conseils'],
        ['Construire une présentation qui convainc', 'Tutoriels'],
        ['Prendre des notes efficacement', 'Tutoriels'],
        ['Organiser sa bibliothèque numérique', 'Conseils'],
        ['Les erreurs classiques d\'un diaporama', 'Tutoriels'],
        ['PDF ou PowerPoint : quel format choisir', 'Tutoriels'],
        ['Se former en ligne sans se disperser', 'Business'],
    ];
    $blogBody = "<p>Bienvenue dans le carnet de la bibliothèque. Nous y partageons nos conseils de lecture, nos méthodes de travail et nos astuces pour tirer le meilleur de chaque titre.</p><h2>Nos conseils</h2><p>La régularité et l'organisation sont vos meilleures alliées. Commencez petit, avancez chapitre par chapitre, prenez des notes.</p><ul><li>Choisissez un titre adapté à votre niveau.</li><li>Fixez-vous un créneau de lecture régulier.</li><li>Reprenez vos notes une semaine plus tard.</li></ul><p>Retrouvez tout le détail dans nos livres et présentations, avec des fiches pratiques prêtes à l'emploi.</p>";
    $blog = [];
    foreach ($blogTopics as $i => $t) {
        $blog[] = ['id' => $i + 1, 'title' => $t[0], 'slug' => slugify($t[0]), 'category' => $t[1],
            'excerpt' => 'Mes conseils pour ' . mb_strtolower($t[0]) . '.', 'body' => $blogBody, 'image' => null,
            'author' => 'La Bibliothèque', 'tags' => $t[1] . ', lecture, conseils', 'status' => 'published',
            'views' => mt_rand(80, 3200), 'published_at' => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 150))];
    }

    /* ---------------- Témoignages ---------------- */
    $testiBodies = [
        'Grâce à ce livre j\'ai enfin structuré mon projet. Merci infiniment !',
        'Les explications sont limpides et la lecture en ligne est super pratique.',
        'J\'ai adoré pouvoir lire un aperçu avant d\'acheter. Contenu au top.',
        'Paiement BaridiMob simple et accès immédiat après validation.',
        'Les présentations PowerPoint se lisent parfaitement dans le navigateur.',
        'Je recommande à toute personne qui veut se former sérieusement.',
    ];
    $testiRoles = ['Client vérifié', 'Formatrice indépendante', 'Étudiante', 'Lecteur fidèle'];
    $testimonials = [];
    for ($i = 1; $i <= 8; $i++) {
        $fn = $firstNames[mt_rand(0, count($firstNames) - 1)];
        $testimonials[] = ['id' => $i, 'name' => $fn . ' ' . mb_substr($lastNames[mt_rand(0, count($lastNames) - 1)], 0, 1) . '.',
            'role' => $testiRoles[mt_rand(0, count($testiRoles) - 1)], 'avatar' => null, 'rating' => 5,
            'body' => $testiBodies[mt_rand(0, count($testiBodies) - 1)], 'position' => $i];
    }

    return compact('settings', 'admins', 'categories', 'books', 'reviews',
        'customers', 'orders', 'orderItems', 'access', 'couponRows', 'blog', 'testimonials');
}
