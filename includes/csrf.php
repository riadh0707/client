<?php
/**
 * csrf.php - protection CSRF par jeton de session + limitation de débit.
 */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Champ caché prêt à insérer dans un formulaire. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton reçu ; interrompt la requête si invalide. */
function csrf_check(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => 'Jeton de sécurité invalide.'], 419);
        }
        http_response_code(419);
        exit('Jeton de sécurité invalide. Veuillez rafraîchir la page.');
    }
}

/**
 * Limitation de débit simple par clé (ex. tentatives de connexion).
 * Retourne false si la limite est dépassée.
 */
function rate_limit(string $key, ?int $max = null, int $window = 60): bool
{
    $max = $max ?? ($GLOBALS['config']['security']['rate_limit'] ?? 30);
    $now = time();
    $bucket = &$_SESSION['_rl'][$key];
    if (!is_array($bucket) || ($now - ($bucket['start'] ?? 0)) > $window) {
        $bucket = ['start' => $now, 'count' => 0];
    }
    $bucket['count']++;
    return $bucket['count'] <= $max;
}
