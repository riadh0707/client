<?php
/**
 * data.php — Source unique des données de démonstration.
 *
 * Génère de façon DÉTERMINISTE (mt_srand fixe) l'ensemble du contenu fictif :
 * marques, catégories, ~120 produits, avis, articles de blog, témoignages,
 * promotions, clients et commandes. Utilisé à la fois par l'installateur
 * (database/install.php) et par le générateur de database.sql.
 *
 * Aucune image binaire n'est requise : les visuels sont rendus par un
 * placeholder SVG élégant (assets/placeholder.php), léger et sans requête
 * externe — idéal pour l'hébergement AwardSpace Free.
 */

function demo_data(): array
{
    mt_srand(20240607); // reproductibilité totale

    /* ==================================================================
     |  Paramètres du site (table settings)
     * ================================================================== */
    $settings = [
        'site_name'         => 'Bella Rosa',
        'site_tagline'      => 'La beauté, sublimée.',
        'meta_description'  => 'Bella Rosa — Maquillage, soins, parfums et accessoires beauté premium. Marques d\'exception, livraison rapide dans les 58 wilayas d\'Algérie.',
        'contact_email'     => 'contact@bella-rosa.dz',
        'contact_phone'     => '+213 561 23 45 67',
        'contact_address'   => '14 Boulevard Krim Belkacem, Alger Centre, Alger',
        'free_shipping_min' => '8000',
        'shipping_flat'     => '600',
        'primary_color'     => '#b76e79',
        'accent_color'      => '#c9a24b',
        'social_instagram'  => 'https://instagram.com/bellarosa',
        'social_facebook'   => 'https://facebook.com/bellarosa',
        'social_tiktok'     => 'https://tiktok.com/@bellarosa',
        'map_embed'         => '36.7538,3.0588',
        'announcement'      => 'Livraison offerte dès 8 000 DA d\'achat • Paiement à la livraison partout en Algérie',
    ];

    /* ==================================================================
     |  Administrateur par défaut
     * ================================================================== */
    $admins = [[
        'name'     => 'Administratrice',
        'email'    => 'admin@bella-rosa.dz',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role'     => 'super',
    ]];

    /* ==================================================================
     |  Marques
     * ================================================================== */
    $brandNames = [
        'Bella Rosa', 'Lumière Paris', 'Rose Éclat', 'Nour Cosmetics', 'Saharienne',
        'Velvet Bloom', 'Atlas Botanics', 'Kohl & Co', 'Pure Élixir', 'Ambre Noir',
    ];
    $brandDesc = [
        'Notre marque maison, pensée pour révéler votre éclat naturel.',
        'L\'élégance parisienne au service de votre beauté.',
        'Des formules délicates inspirées de la rose de Damas.',
        'Cosmétiques modernes aux actifs puissants et sensoriels.',
        'La richesse des ingrédients du Sud algérien.',
        'Le velouté d\'une peau parfaitement sublimée.',
        'La botanique de l\'Atlas dans des soins d\'exception.',
        'Le regard intense, la tradition du khôl revisitée.',
        'Des élixirs purs, sans compromis sur la qualité.',
        'Des parfums profonds, ambrés et inoubliables.',
    ];
    $brands = [];
    foreach ($brandNames as $i => $name) {
        $brands[] = [
            'id'          => $i + 1,
            'name'        => $name,
            'slug'        => slugify($name),
            'description' => $brandDesc[$i],
            'logo'        => null,
        ];
    }

    /* ==================================================================
     |  Catégories (parents + sous-catégories) — 15 au total
     * ================================================================== */
    $catTree = [
        ['Maquillage',        'sparkles', ['Teint', 'Yeux', 'Lèvres']],
        ['Soins Visage',      'droplet',  ['Nettoyants', 'Sérums', 'Crèmes']],
        ['Soins Corps',       'flower',   []],
        ['Soins Cheveux',     'wind',     []],
        ['Parfums',           'spray',    ['Femme', 'Homme']],
        ['Onglerie',          'brush',    ['Vernis']],
        ['Accessoires Beauté','gem',      []],
    ];
    $categories = [];
    $cid = 0;
    foreach ($catTree as $node) {
        [$name, $icon, $children] = $node;
        $parentId = ++$cid;
        $categories[] = [
            'id' => $parentId, 'parent_id' => null, 'name' => $name,
            'slug' => slugify($name), 'icon' => $icon, 'image' => slugify($name) . '.jpg',
            'description' => 'Découvrez notre sélection ' . mb_strtolower($name) . ' : des produits premium choisis avec soin.',
            'seo_title' => $name . ' premium en Algérie | Bella Rosa',
            'position' => $parentId,
        ];
        foreach ($children as $child) {
            $categories[] = [
                'id' => ++$cid, 'parent_id' => $parentId, 'name' => $child,
                'slug' => slugify($name . '-' . $child), 'icon' => $icon, 'image' => slugify($name . '-' . $child) . '.jpg',
                'description' => $child . ' — ' . $name . '.',
                'seo_title' => $child . ' — ' . $name . ' | Bella Rosa',
                'position' => $cid,
            ];
        }
    }
    // Ids réels des catégories racines, dans l'ordre de $catTree.
    $rootIds = array_values(array_map(fn($c) => $c['id'], array_filter($categories, fn($c) => $c['parent_id'] === null)));
    // Index des catégories "feuilles" affectables aux produits.
    $leafByParent = [];
    foreach ($categories as $c) {
        if ($c['parent_id'] !== null) {
            $leafByParent[$c['parent_id']][] = $c['id'];
        }
    }

    /* ==================================================================
     |  Produits (~120)
     * ================================================================== */
    // Vocabulaire par catégorie parente pour des noms réalistes.
    $vocab = [
        1 => [ // Maquillage
            'adjectives' => ['Velours', 'Éclat', 'Satin', 'Lumière', 'Intense', 'Nude', 'Radiant', 'Mat Infini'],
            'nouns' => ['Fond de teint', 'Rouge à lèvres', 'Mascara', 'Palette de fards', 'Blush', 'Anti-cernes', 'Highlighter', 'Eyeliner', 'Gloss', 'Poudre libre'],
        ],
        2 => [ // Soins Visage
            'adjectives' => ['Hydra', 'Éclat', 'Repulpant', 'Purifiant', 'Anti-âge', 'Apaisant', 'Régénérant'],
            'nouns' => ['Sérum vitamine C', 'Crème hydratante', 'Gel nettoyant', 'Masque à l\'argile', 'Contour des yeux', 'Eau micellaire', 'Sérum acide hyaluronique', 'Exfoliant doux'],
        ],
        3 => [ // Soins Corps
            'adjectives' => ['Nourrissant', 'Velouté', 'Gourmand', 'Soyeux', 'Tonifiant'],
            'nouns' => ['Lait corporel', 'Gommage sucré', 'Beurre de karité', 'Huile sèche', 'Gel douche', 'Crème mains'],
        ],
        4 => [ // Soins Cheveux
            'adjectives' => ['Réparateur', 'Brillance', 'Volume', 'Nutrition', 'Anti-frisottis'],
            'nouns' => ['Shampooing', 'Après-shampooing', 'Masque capillaire', 'Huile d\'argan', 'Sérum pointes', 'Spray thermo-protecteur'],
        ],
        5 => [ // Parfums
            'adjectives' => ['Rose', 'Ambre', 'Oud', 'Fleur d\'oranger', 'Jasmin', 'Musc Blanc', 'Vanille'],
            'nouns' => ['Eau de parfum', 'Eau de toilette', 'Brume parfumée', 'Extrait de parfum'],
        ],
        6 => [ // Onglerie
            'adjectives' => ['Gel', 'Brillance', 'Pastel', 'Métallisé', 'Nude', 'Rouge Passion'],
            'nouns' => ['Vernis à ongles', 'Top coat', 'Base coat', 'Kit manucure', 'Huile cuticules'],
        ],
        7 => [ // Accessoires
            'adjectives' => ['Pro', 'Kabuki', 'Précision', 'Éponge', 'Bambou'],
            'nouns' => ['Pinceau maquillage', 'Set de pinceaux', 'Éponge à fond de teint', 'Miroir LED', 'Trousse beauté', 'Bandeau spa'],
        ],
    ];

    $ingredientsPool = [
        'Aqua, Glycerin, Rosa Damascena Flower Water, Tocopherol, Sodium Hyaluronate, Parfum.',
        'Aqua, Butyrospermum Parkii (Karité), Argania Spinosa Oil, Panthenol, Citric Acid.',
        'Aqua, Niacinamide, Zinc PCA, Aloe Barbadensis, Allantoin, Sodium Benzoate.',
        'Alcohol Denat, Parfum, Aqua, Limonene, Linalool, Citronellol, Geraniol.',
    ];
    $usagePool = [
        'Appliquez matin et soir sur peau propre et sèche, en évitant le contour des yeux.',
        'Déposez une noisette de produit et massez délicatement jusqu\'à absorption complète.',
        'Vaporisez à 20 cm de la peau, sur les points de pulsation.',
        'Utilisez quotidiennement pour des résultats visibles dès 4 semaines.',
    ];

    $products = [];
    $reviews = [];
    $pid = 0;
    $rid = 0;
    $reviewAuthors = ['Amina B.', 'Lydia K.', 'Sarah M.', 'Nesrine T.', 'Yasmine H.', 'Feriel D.', 'Katia S.', 'Rania L.', 'Meriem A.', 'Sonia G.', 'Ines R.', 'Dalia F.'];
    $reviewTitles = ['Coup de cœur !', 'Je recommande', 'Très bon produit', 'Parfait', 'Bonne surprise', 'Excellent rapport qualité/prix', 'Conforme à mes attentes'];
    $reviewBodies = [
        'Texture agréable et résultat visible rapidement. Je rachèterai sans hésiter.',
        'Livraison rapide et emballage soigné. Le produit tient toutes ses promesses.',
        'J\'adore le parfum et le fini sur la peau. Un vrai plaisir au quotidien.',
        'Efficace et non irritant, même sur ma peau sensible. Bravo Bella Rosa.',
        'La qualité est au rendez-vous, je le conseille à toutes mes amies.',
    ];

    // Photos produits (uploads/products/<préfixe>-NN{a|b}.jpg) — voir CREDITS-IMAGES.md.
    $imgPrefix = [1 => 'maquillage', 2 => 'soins-visage', 3 => 'soins-corps', 4 => 'soins-cheveux', 5 => 'parfums', 6 => 'onglerie', 7 => 'accessoires'];
    $imgCount  = [1 => 13, 2 => 15, 3 => 9, 4 => 6, 5 => 7, 6 => 4, 7 => 7];
    $nounPools = [
        'Rouge à lèvres' => [1, 10, 12], 'Gloss' => [10, 1, 12],
        'Palette de fards' => [6, 9, 3, 4], 'Blush' => [11, 7, 2], 'Highlighter' => [7, 11, 2], 'Poudre libre' => [2, 11, 7],
        'Fond de teint' => [11, 12, 5], 'Anti-cernes' => [12, 11, 5], 'Mascara' => [9, 3, 13, 8], 'Eyeliner' => [3, 9, 8, 13],
        'Sérum vitamine C' => [7, 11, 12, 14], 'Sérum acide hyaluronique' => [11, 14, 7, 12], 'Crème hydratante' => [4, 6, 10, 13],
        'Gel nettoyant' => [3, 5, 9], "Masque à l'argile" => [15, 6, 9], 'Contour des yeux' => [8, 13, 4],
        'Eau micellaire' => [1, 8, 2], 'Exfoliant doux' => [5, 4, 10],
        'Pinceau maquillage' => [1, 3, 4], 'Set de pinceaux' => [2, 4, 3], 'Éponge à fond de teint' => [6, 1],
        'Trousse beauté' => [7, 6], 'Bandeau spa' => [6, 5], 'Miroir LED' => [6, 2],
    ];
    $nounLeaf = [
        'Fond de teint' => 'maquillage-teint', 'Anti-cernes' => 'maquillage-teint', 'Poudre libre' => 'maquillage-teint',
        'Blush' => 'maquillage-teint', 'Highlighter' => 'maquillage-teint',
        'Mascara' => 'maquillage-yeux', 'Palette de fards' => 'maquillage-yeux', 'Eyeliner' => 'maquillage-yeux',
        'Rouge à lèvres' => 'maquillage-levres', 'Gloss' => 'maquillage-levres',
        'Gel nettoyant' => 'soins-visage-nettoyants', 'Eau micellaire' => 'soins-visage-nettoyants',
        'Exfoliant doux' => 'soins-visage-nettoyants', "Masque à l'argile" => 'soins-visage-nettoyants',
        'Sérum vitamine C' => 'soins-visage-serums', 'Sérum acide hyaluronique' => 'soins-visage-serums',
        'Crème hydratante' => 'soins-visage-cremes', 'Contour des yeux' => 'soins-visage-cremes',
    ];
    $catIdBySlug = array_column($categories, 'id', 'slug');
    $imgCursor = [];
    $productImages = [];

    $perParent = [1 => 30, 2 => 24, 3 => 14, 4 => 14, 5 => 18, 6 => 12, 7 => 8]; // = 120
    foreach ($perParent as $parentId => $count) {
        $v = $vocab[$parentId];
        for ($n = 0; $n < $count; $n++) {
            $pid++;
            $adj  = $v['adjectives'][mt_rand(0, count($v['adjectives']) - 1)];
            $noun = $v['nouns'][mt_rand(0, count($v['nouns']) - 1)];
            $brand = $brands[mt_rand(0, count($brands) - 1)];
            $name = $noun . ' ' . $adj;

            // Catégorie : feuille si dispo, sinon le parent.
            // ($parentId est le rang 1..7 de l'univers ; $rootId son id réel dans $categories.)
            $rootId = $rootIds[$parentId - 1];
            $catId = isset($leafByParent[$rootId])
                ? $leafByParent[$rootId][mt_rand(0, count($leafByParent[$rootId]) - 1)]
                : $rootId;
            // Sous-catégorie cohérente avec le type de produit quand elle existe.
            if (isset($nounLeaf[$noun], $catIdBySlug[$nounLeaf[$noun]])) {
                $catId = $catIdBySlug[$nounLeaf[$noun]];
            }

            $base   = mt_rand(1200, 14500);
            $onSale = mt_rand(1, 100) <= 32;
            $old    = $onSale ? (int) round($base * (mt_rand(115, 145) / 100)) : null;
            $rating = round(mt_rand(38, 50) / 10, 1);
            $rCount = mt_rand(3, 180);

            $products[] = [
                'id'            => $pid,
                'name'          => $name,
                'slug'          => slugify($name) . '-' . $pid,
                'sku'           => 'BR-' . str_pad((string) $pid, 4, '0', STR_PAD_LEFT),
                'brand_id'      => $brand['id'],
                'category_id'   => $catId,
                'short_desc'    => $adj . ' — ' . $noun . ' signé ' . $brand['name'] . ', pour un résultat professionnel.',
                'long_desc'     => "Le $name de $brand[name] conjugue efficacité et sensorialité. Sa formule enrichie en actifs sélectionnés sublime votre routine beauté quotidienne. Testé sous contrôle dermatologique, il convient à tous les types de peaux et s'intègre parfaitement à un rituel de soin premium.",
                'ingredients'   => $ingredientsPool[mt_rand(0, count($ingredientsPool) - 1)],
                'usage_tips'    => $usagePool[mt_rand(0, count($usagePool) - 1)],
                'price'         => $base,
                'old_price'     => $old,
                'stock'         => mt_rand(0, 1) && mt_rand(1, 100) <= 8 ? 0 : mt_rand(4, 120),
                'weight'        => mt_rand(30, 400),
                'volume'        => [30, 50, 75, 100, 150, 200][mt_rand(0, 5)] . ' ml',
                'rating'        => $rating,
                'reviews_count' => $rCount,
                'is_featured'   => mt_rand(1, 100) <= 18 ? 1 : 0,
                'is_new'        => mt_rand(1, 100) <= 22 ? 1 : 0,
                'is_bestseller' => mt_rand(1, 100) <= 20 ? 1 : 0,
                'on_sale'       => $onSale ? 1 : 0,
                'status'        => 'active',
                'video_url'     => null,
            ];

            // Galerie photo (3 visuels) : d'abord une photo correspondant au
            // type de produit, puis deux visuels de la catégorie. Sélection par
            // compteurs (aucun appel à mt_rand : la génération reste identique).
            $pref  = $imgPrefix[$parentId];
            $pool  = $nounPools[$noun] ?? range(1, $imgCount[$parentId]);
            $seen  = [];
            for ($g = 0; $g < 3; $g++) {
                $src = $g === 0 ? $pool : range(1, $imgCount[$parentId]);
                $key = $pref . ($g === 0 ? $noun : '#cat');
                $i   = $imgCursor[$key] = (($imgCursor[$key] ?? -1) + 1);
                $num = $src[$i % count($src)];
                $variant = intdiv($i, count($src)) % 2 ? 'b' : 'a';
                $file = sprintf('%s-%02d%s.jpg', $pref, $num, $variant);
                if (isset($seen[$file])) { $file = sprintf('%s-%02d%s.jpg', $pref, $num, $variant === 'a' ? 'b' : 'a'); }
                $seen[$file] = true;
                $productImages[] = ['product_id' => $pid, 'file' => $file, 'position' => $g];
            }

            // 0 à 4 avis publiés par produit.
            $numReviews = mt_rand(0, 4);
            for ($k = 0; $k < $numReviews; $k++) {
                $rid++;
                $reviews[] = [
                    'id'          => $rid,
                    'product_id'  => $pid,
                    'customer_id' => null,
                    'author_name' => $reviewAuthors[mt_rand(0, count($reviewAuthors) - 1)],
                    'rating'      => mt_rand(3, 5),
                    'title'       => $reviewTitles[mt_rand(0, count($reviewTitles) - 1)],
                    'body'        => $reviewBodies[mt_rand(0, count($reviewBodies) - 1)],
                    'status'      => mt_rand(1, 100) <= 88 ? 'approved' : 'pending',
                    'created_at'  => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 220)),
                ];
            }
        }
    }

    /* ==================================================================
     |  Clients (50)
     * ================================================================== */
    $firstNames = ['Amina', 'Lydia', 'Sarah', 'Nesrine', 'Yasmine', 'Feriel', 'Katia', 'Rania', 'Meriem', 'Sonia', 'Ines', 'Dalia', 'Nadia', 'Salima', 'Hana', 'Lina', 'Wassila', 'Chaima', 'Manel', 'Kenza'];
    $lastNames  = ['Benali', 'Kaci', 'Meziane', 'Toumi', 'Haddad', 'Cherif', 'Saidi', 'Lounis', 'Ammar', 'Gacem', 'Rahmani', 'Ferhat', 'Belkacem', 'Ouali', 'Zerrouki'];
    $wilayas    = ['Alger', 'Oran', 'Constantine', 'Blida', 'Sétif', 'Annaba', 'Béjaïa', 'Tizi Ouzou', 'Batna', 'Tlemcen'];
    $customers = [];
    for ($i = 1; $i <= 50; $i++) {
        $fn = $firstNames[mt_rand(0, count($firstNames) - 1)];
        $ln = $lastNames[mt_rand(0, count($lastNames) - 1)];
        $customers[] = [
            'id'             => $i,
            'first_name'     => $fn,
            'last_name'      => $ln,
            'email'          => strtolower($fn . '.' . $ln . $i) . '@email.dz',
            'phone'          => '0' . mt_rand(5, 7) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password'       => password_hash('client123', PASSWORD_DEFAULT),
            'loyalty_points' => mt_rand(0, 850),
            'wilaya'         => $wilayas[mt_rand(0, count($wilayas) - 1)],
            'created_at'     => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 300)),
        ];
    }
    // Un client de démonstration à identifiants connus.
    $customers[0]['email'] = 'client@bella-rosa.dz';
    $customers[0]['first_name'] = 'Yasmine';
    $customers[0]['last_name'] = 'Demo';

    /* ==================================================================
     |  Commandes (40)
     * ================================================================== */
    $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    $statusWeights = ['pending' => 15, 'confirmed' => 20, 'shipped' => 20, 'delivered' => 40, 'cancelled' => 5];
    $payMethods = ['cod', 'baridimob', 'ccp'];
    $orders = [];
    $orderItems = [];
    $oitemId = 0;
    for ($i = 1; $i <= 40; $i++) {
        $cust = $customers[mt_rand(0, count($customers) - 1)];
        $numItems = mt_rand(1, 4);
        $subtotal = 0;
        $itemsForOrder = [];
        for ($j = 0; $j < $numItems; $j++) {
            $p = $products[mt_rand(0, count($products) - 1)];
            $qty = mt_rand(1, 3);
            $subtotal += $p['price'] * $qty;
            $oitemId++;
            $itemsForOrder[] = [
                'id' => $oitemId, 'order_id' => $i, 'product_id' => $p['id'],
                'name' => $p['name'], 'price' => $p['price'], 'qty' => $qty,
            ];
        }
        $shipping = $subtotal >= 8000 ? 0 : 600;
        // Choix pondéré du statut.
        $r = mt_rand(1, 100); $acc = 0; $status = 'delivered';
        foreach ($statusWeights as $s => $w) { $acc += $w; if ($r <= $acc) { $status = $s; break; } }

        $orders[] = [
            'id'             => $i,
            'reference'      => 'CMD-' . date('Y') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'customer_id'    => $cust['id'],
            'customer_name'  => $cust['first_name'] . ' ' . $cust['last_name'],
            'email'          => $cust['email'],
            'phone'          => $cust['phone'],
            'address'        => mt_rand(1, 200) . ' Rue des Oliviers',
            'wilaya'         => $cust['wilaya'],
            'subtotal'       => $subtotal,
            'shipping'       => $shipping,
            'discount'       => 0,
            'total'          => $subtotal + $shipping,
            'payment_method' => $payMethods[mt_rand(0, count($payMethods) - 1)],
            'status'         => $status,
            'note'           => '',
            'created_at'     => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 120)),
        ];
        foreach ($itemsForOrder as $it) {
            $orderItems[] = $it;
        }
    }

    /* ==================================================================
     |  Coupons / Promotions (10)
     * ================================================================== */
    $coupons = [
        ['code' => 'BIENVENUE10', 'type' => 'percent', 'value' => 10, 'min_amount' => 3000, 'desc' => '-10% première commande'],
        ['code' => 'ROSE15',      'type' => 'percent', 'value' => 15, 'min_amount' => 6000, 'desc' => '-15% dès 6000 DA'],
        ['code' => 'BEAUTE500',   'type' => 'fixed',   'value' => 500, 'min_amount' => 4000, 'desc' => '-500 DA'],
        ['code' => 'VIP20',       'type' => 'percent', 'value' => 20, 'min_amount' => 12000, 'desc' => '-20% clientes VIP'],
        ['code' => 'FLASH25',     'type' => 'percent', 'value' => 25, 'min_amount' => 8000, 'desc' => 'Vente flash -25%'],
        ['code' => 'PARFUM1000',  'type' => 'fixed',   'value' => 1000, 'min_amount' => 9000, 'desc' => '-1000 DA parfums'],
        ['code' => 'ETE30',       'type' => 'percent', 'value' => 30, 'min_amount' => 15000, 'desc' => 'Soldes d\'été -30%'],
        ['code' => 'LIVRAISON',   'type' => 'fixed',   'value' => 600, 'min_amount' => 3000, 'desc' => 'Livraison offerte'],
        ['code' => 'GLOW12',      'type' => 'percent', 'value' => 12, 'min_amount' => 5000, 'desc' => '-12% soins'],
        ['code' => 'MAKEUP18',    'type' => 'percent', 'value' => 18, 'min_amount' => 7000, 'desc' => '-18% maquillage'],
    ];
    foreach ($coupons as $i => &$c) {
        $c['id'] = $i + 1;
        $c['expires_at'] = date('Y-m-d', time() + 86400 * mt_rand(20, 120));
        $c['usage_limit'] = mt_rand(50, 500);
        $c['used'] = mt_rand(0, 40);
        $c['active'] = 1;
    }
    unset($c);

    /* ==================================================================
     |  Blog (25 articles)
     * ================================================================== */
    $blogTopics = [
        ['Routine skincare : les 5 étapes essentielles', 'Soins'],
        ['Comment choisir son fond de teint parfait', 'Maquillage'],
        ['Les bienfaits de l\'huile d\'argan sur les cheveux', 'Cheveux'],
        ['Tendances make-up de la saison', 'Tendances'],
        ['Sérum vitamine C : mode d\'emploi', 'Soins'],
        ['Le guide ultime du contouring', 'Tutoriels'],
        ['Parfum : trouver sa signature olfactive', 'Parfums'],
        ['Peau sensible : les gestes à adopter', 'Soins'],
        ['Manucure parfaite à la maison', 'Onglerie'],
        ['Acide hyaluronique : l\'allié hydratation', 'Soins'],
        ['Maquillage nude pour un teint frais', 'Tutoriels'],
        ['Prendre soin de sa peau en hiver', 'Conseils'],
        ['Les erreurs à éviter avec son mascara', 'Maquillage'],
        ['Rituel beauté du soir en 10 minutes', 'Conseils'],
        ['Cheveux bouclés : la routine idéale', 'Cheveux'],
        ['Comment appliquer un rouge à lèvres longue tenue', 'Tutoriels'],
        ['Les ingrédients stars des soins anti-âge', 'Soins'],
        ['Détox de la peau : mythe ou réalité ?', 'Conseils'],
        ['Bien choisir ses pinceaux de maquillage', 'Accessoires'],
        ['Le smoky eye pas à pas', 'Tutoriels'],
        ['Protéger sa peau du soleil en Algérie', 'Conseils'],
        ['Beauté clean : décrypter les étiquettes', 'Tendances'],
        ['Masques maison aux ingrédients naturels', 'Conseils'],
        ['Le blush, touche finale d\'un teint réussi', 'Maquillage'],
        ['Parfums d\'ambiance : l\'art de la maison parfumée', 'Parfums'],
    ];
    $blogAuthors = ['Yasmine H.', 'La rédaction Bella Rosa', 'Sarah M.', 'Dr. Lina Beauté'];
    $blogBody = "<p>Chez Bella Rosa, nous croyons que la beauté commence par des gestes simples et des produits d'exception. Dans cet article, notre équipe partage ses conseils d'experts pour sublimer votre routine au quotidien.</p><h2>Nos recommandations</h2><p>Adoptez des soins adaptés à votre type de peau et privilégiez la régularité. La constance est la clé de résultats visibles et durables.</p><ul><li>Nettoyez matin et soir en douceur.</li><li>Hydratez avec des actifs ciblés.</li><li>Protégez votre peau des agressions extérieures.</li></ul><h2>Le mot de la fin</h2><p>Prenez le temps de vous chouchouter : votre peau vous le rendra. Découvrez notre sélection pour composer le rituel qui vous ressemble.</p>";
    $blog = [];
    foreach ($blogTopics as $i => $t) {
        $blog[] = [
            'id'           => $i + 1,
            'title'        => $t[0],
            'slug'         => slugify($t[0]),
            'category'     => $t[1],
            'excerpt'      => 'Découvrez nos conseils d\'experts pour ' . mb_strtolower($t[0]) . ' et sublimer votre beauté au quotidien.',
            'body'         => $blogBody,
            'image'        => sprintf('blog-%02d.jpg', $i + 1),
            'author'       => $blogAuthors[mt_rand(0, count($blogAuthors) - 1)],
            'tags'         => $t[1] . ', beauté, conseils',
            'status'       => 'published',
            'views'        => mt_rand(120, 5400),
            'published_at' => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 200)),
        ];
    }

    /* ==================================================================
     |  Témoignages (15)
     * ================================================================== */
    $testimonialBodies = [
        'Un service impeccable et des produits de grande qualité. Je suis cliente fidèle depuis des mois !',
        'La livraison est rapide et l\'emballage est magnifique. On sent le souci du détail.',
        'Enfin une boutique beauté algérienne à la hauteur des grandes marques internationales.',
        'Mes soins Bella Rosa ont transformé ma peau. Je recommande les yeux fermés.',
        'Le conseil clientèle est adorable et de bon conseil. Merci pour votre professionnalisme.',
        'Des parfums qui tiennent toute la journée et des prix justes. Que demander de plus ?',
        'J\'ai offert un coffret à ma sœur, elle était aux anges. Présentation luxueuse.',
        'La qualité premium se ressent dès l\'ouverture du colis. Bravo à toute l\'équipe.',
    ];
    $testimonialRoles = ['Cliente fidèle', 'Make-up artist', 'Cliente vérifiée', 'Esthéticienne', 'Influenceuse beauté'];
    $testimonials = [];
    for ($i = 1; $i <= 15; $i++) {
        $fn = $firstNames[mt_rand(0, count($firstNames) - 1)];
        $testimonials[] = [
            'id'       => $i,
            'name'     => $fn . ' ' . mb_substr($lastNames[mt_rand(0, count($lastNames) - 1)], 0, 1) . '.',
            'role'     => $testimonialRoles[mt_rand(0, count($testimonialRoles) - 1)],
            'avatar'   => sprintf('avatar-%02d.jpg', $i),
            'rating'   => mt_rand(4, 5),
            'body'     => $testimonialBodies[mt_rand(0, count($testimonialBodies) - 1)],
            'position' => $i,
        ];
    }

    return compact(
        'settings', 'admins', 'brands', 'categories', 'products', 'reviews',
        'customers', 'orders', 'orderItems', 'coupons', 'blog', 'testimonials', 'productImages'
    );
}
