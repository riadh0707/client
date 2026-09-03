<?php
/**
 * ---------------------------------------------------------------------------
 *  La Bibliothèque Numérique — المكتبة الرقمية
 *  Configuration principale (EXEMPLE)
 * ---------------------------------------------------------------------------
 *  Copiez ce fichier vers  config/config.php  puis renseignez vos identifiants.
 *  Ne committez jamais config.php.
 *
 *  Compatible AwardSpace Free : une seule base MySQL, PDO. Un mode SQLite est
 *  fourni pour une démo locale immédiate (voir DB_DRIVER).
 * ---------------------------------------------------------------------------
 */

return [

    /* -------------------------------------------------------------------
     |  Base de données
     * ------------------------------------------------------------------- */
    'db' => [
        'driver'      => 'mysql',            // 'mysql' (AwardSpace) | 'sqlite' (démo)
        'host'        => 'localhost',
        'port'        => 3306,
        'name'        => 'bibliotheque',
        'user'        => 'root',
        'pass'        => '',
        'charset'     => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/../database/bibliotheque.sqlite',
    ],

    /* -------------------------------------------------------------------
     |  Application
     * ------------------------------------------------------------------- */
    'app' => [
        'name'            => 'La Bibliothèque Numérique',
        'name_ar'         => 'المكتبة الرقمية',
        'tagline'         => 'Des livres et des présentations, partout avec vous.',
        'base_url'        => '',            // vide = auto-détection
        'env'             => 'production',
        'timezone'        => 'Africa/Algiers',
        'locale'          => 'fr',
        'currency'        => 'DZD',
        'currency_symbol' => 'DA',
    ],

    /* -------------------------------------------------------------------
     |  Sécurité — php -r "echo bin2hex(random_bytes(32));"
     * ------------------------------------------------------------------- */
    'security' => [
        'app_key'          => 'CHANGEZ_MOI_avec_une_cle_aleatoire',
        'session_name'     => 'biblio_sess',
        'session_lifetime' => 60 * 60 * 24 * 14,
        'cookie_secure'    => false,
        'rate_limit'       => 30,
    ],

    /* -------------------------------------------------------------------
     |  Uploads (limites AwardSpace : pensez à upload_max_filesize)
     * ------------------------------------------------------------------- */
    'uploads' => [
        'max_pdf_mb'   => 40,
        'max_image_mb' => 4,
    ],

    'mail' => [
        'enabled'    => false,
        'from_name'  => 'La Bibliothèque Numérique',
        'from_email' => 'no-reply@bibliotheque-numerique.dz',
    ],
];
