<?php
/**
 * Bootstrap - point d'entrée commun à toutes les pages.
 * Charge la configuration, initialise la session, la base de données,
 * les helpers, la sécurité et les variables globales de la boutique.
 */

if (defined('BELLA_BOOTSTRAPPED')) {
    return;
}
define('BELLA_BOOTSTRAPPED', true);

/* -------------------------------------------------------------------------
 |  Chemins
 * ------------------------------------------------------------------------- */
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

/* -------------------------------------------------------------------------
 |  Configuration
 * ------------------------------------------------------------------------- */
$configFile = CONFIG_PATH . '/config.php';
if (!file_exists($configFile)) {
    // Repli sur l'exemple pour permettre une première prise en main.
    $configFile = CONFIG_PATH . '/config.example.php';
}
$config = require $configFile;
$GLOBALS['config'] = $config;

/* -------------------------------------------------------------------------
 |  Environnement / erreurs
 * ------------------------------------------------------------------------- */
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

if (($config['app']['env'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
    ini_set('display_errors', '0');
}

/* -------------------------------------------------------------------------
 |  Session sécurisée
 * ------------------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['security']['session_name'] ?? 'bella_sess');
    session_set_cookie_params([
        'lifetime' => $config['security']['session_lifetime'] ?? 604800,
        'path'     => '/',
        'secure'   => $config['security']['cookie_secure'] ?? false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* -------------------------------------------------------------------------
 |  URL de base (auto-détection si non définie)
 * ------------------------------------------------------------------------- */
if (empty($config['app']['base_url'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Racine du projet relative au docroot (gère les sous-dossiers).
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    // Les scripts placés dans un sous-dossier connu remontent à la racine projet.
    $scriptDir = preg_replace('#/(admin|customer|public|api|database)$#', '', $scriptDir);
    $scriptDir = rtrim($scriptDir, '/');
    $config['app']['base_url'] = $scheme . '://' . $host . $scriptDir;
    $GLOBALS['config'] = $config;
}
define('BASE_URL', rtrim($config['app']['base_url'], '/'));

/* -------------------------------------------------------------------------
 |  Chargement des briques applicatives
 * ------------------------------------------------------------------------- */
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';

/* -------------------------------------------------------------------------
 |  Paramètres de la boutique (table settings), mis en cache en session
 * ------------------------------------------------------------------------- */
$GLOBALS['settings'] = load_settings();
