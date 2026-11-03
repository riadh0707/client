<?php
/**
 * ---------------------------------------------------------------------------
 *  Bella Rosa — Configuration principale (EXEMPLE)
 * ---------------------------------------------------------------------------
 *  Copiez ce fichier vers  config/config.php  puis renseignez vos identifiants.
 *  Ne committez jamais config.php (il est ignoré par .gitignore).
 *
 *  Compatible AwardSpace Free : une seule base MySQL, PDO, aucune extension
 *  exotique requise. Un mode SQLite est fourni pour une démo locale sans
 *  serveur MySQL (voir DB_DRIVER).
 * ---------------------------------------------------------------------------
 */

return [

    /* -------------------------------------------------------------------
     |  Base de données
     |  DB_DRIVER : 'mysql' en production (AwardSpace) ou 'sqlite' pour une
     |  démo locale immédiate, sans installer MySQL.
     * ------------------------------------------------------------------- */
    'db' => [
        'driver'   => 'mysql',            // 'mysql' | 'sqlite'
        'host'     => 'localhost',        // AwardSpace : fdb1234.awardspace.net
        'port'     => 3306,
        'name'     => 'bella_rosa',       // AwardSpace : 1234567_bella
        'user'     => 'root',
        'pass'     => '',
        'charset'  => 'utf8mb4',
        // Chemin du fichier SQLite (utilisé uniquement si driver = sqlite)
        'sqlite_path' => __DIR__ . '/../database/bella_rosa.sqlite',
    ],

    /* -------------------------------------------------------------------
     |  Application
     * ------------------------------------------------------------------- */
    'app' => [
        'name'      => 'Bella Rosa',
        'tagline'   => 'La beauté, sublimée.',
        // URL de base SANS slash final. Vide = auto-détection (recommandé).
        'base_url'  => '',
        'env'       => 'production',      // 'production' masque les erreurs
        'timezone'  => 'Africa/Algiers',
        'locale'    => 'fr',             // fr | ar | en  (architecture i18n)
        'currency'  => 'DZD',
        'currency_symbol' => 'DA',
    ],

    /* -------------------------------------------------------------------
     |  Sécurité
     |  Générez une clé aléatoire :  php -r "echo bin2hex(random_bytes(32));"
     * ------------------------------------------------------------------- */
    'security' => [
        'app_key'          => 'CHANGEZ_MOI_avec_une_cle_aleatoire_de_64_caracteres',
        'session_name'     => 'bella_sess',
        'session_lifetime' => 60 * 60 * 24 * 7,   // 7 jours
        'cookie_secure'    => false,               // true si HTTPS
        'rate_limit'       => 30,                   // requêtes sensibles / minute
    ],

    /* -------------------------------------------------------------------
     |  E-mail (SMTP) — architecture prête, non actif en démo
     * ------------------------------------------------------------------- */
    'mail' => [
        'enabled'    => false,
        'from_name'  => 'Bella Rosa',
        'from_email' => 'no-reply@bella-rosa.dz',
    ],
];
