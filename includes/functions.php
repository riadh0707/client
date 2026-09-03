<?php
/**
 * functions.php - helpers globaux réutilisables (DRY).
 */

/* =========================================================================
 |  Échappement & sortie
 * ========================================================================= */

/** Échappe une chaîne pour un affichage HTML (protection XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Raccourci echo échappé. */
function h($value): void
{
    echo e($value);
}

/* =========================================================================
 |  URLs & assets
 * ========================================================================= */

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Résout l'URL d'une image d'upload avec repli élégant sur un placeholder SVG
 * généré dynamiquement (aucune requête externe, léger pour AwardSpace).
 */
function upload_url(?string $file, string $type = 'products', ?string $label = null): string
{
    if ($file && file_exists(UPLOADS_PATH . '/' . $type . '/' . $file)) {
        return BASE_URL . '/uploads/' . $type . '/' . $file;
    }
    // Placeholder déterministe (couverture vintage générée en SVG).
    $seed = $label ?? $file ?? $type;
    return BASE_URL . '/assets/placeholder.php?t=' . urlencode($type)
        . '&s=' . urlencode($seed) . '&l=' . urlencode(mb_substr((string) ($label ?? ''), 0, 46));
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* =========================================================================
 |  Format / monnaie / dates
 * ========================================================================= */

function money($amount): string
{
    $symbol = $GLOBALS['config']['app']['currency_symbol'] ?? 'DA';
    return number_format((float) $amount, 0, ',', ' ') . ' ' . $symbol;
}

function discount_percent($price, $oldPrice): int
{
    if (!$oldPrice || $oldPrice <= $price) {
        return 0;
    }
    return (int) round((1 - $price / $oldPrice) * 100);
}

function time_ago($datetime): string
{
    $ts   = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
    $diff = time() - $ts;
    if ($diff < 60)     return "à l'instant";
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return date('d/m/Y', $ts);
}

function fdate($datetime, string $format = 'd/m/Y'): string
{
    $ts = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
    return date($format, $ts);
}

/* =========================================================================
 |  Chaînes
 * ========================================================================= */

function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($conv !== false) {
            $text = $conv;
        }
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

function excerpt(?string $text, int $length = 120): string
{
    $text = trim(strip_tags((string) $text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length)) . '…';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $a = mb_substr($parts[0] ?? '', 0, 1);
    $b = mb_substr($parts[count($parts) - 1] ?? '', 0, 1);
    return mb_strtoupper($a . $b);
}

/* =========================================================================
 |  Paramètres du site (table settings)
 * ========================================================================= */

function load_settings(): array
{
    try {
        $rows = Database::all('SELECT `key`, `value` FROM settings');
    } catch (Throwable $e) {
        return []; // Base non installée : on renvoie des valeurs par défaut plus bas.
    }
    $out = [];
    foreach ($rows as $r) {
        $out[$r['key']] = $r['value'];
    }
    return $out;
}

function setting(string $key, $default = null)
{
    return $GLOBALS['settings'][$key] ?? $default;
}

/* =========================================================================
 |  Messages flash
 * ========================================================================= */

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['_flash'][] = ['message' => $message, 'type' => $type];
}

function get_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* =========================================================================
 |  Validation d'entrée
 * ========================================================================= */

function input(string $key, $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $_GET[$key] ?? $default));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/* =========================================================================
 |  Pagination
 * ========================================================================= */

function paginate(int $total, int $perPage, int $current): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $current = max(1, min($current, $pages));
    return [
        'total'   => $total,
        'pages'   => $pages,
        'current' => $current,
        'offset'  => ($current - 1) * $perPage,
        'perPage' => $perPage,
    ];
}

/* =========================================================================
 |  Panier (session)
 * ========================================================================= */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart(), 'qty'));
}

/* =========================================================================
 |  Requête AJAX / JSON
 * ========================================================================= */

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =========================================================================
 |  Produits récemment vus (cookie)
 * ========================================================================= */

function track_recently_viewed(int $productId): void
{
    $ids = array_filter(array_map('intval', explode(',', $_COOKIE['recently_viewed'] ?? '')));
    $ids = array_values(array_unique(array_merge([$productId], $ids)));
    $ids = array_slice($ids, 0, 8);
    setcookie('recently_viewed', implode(',', $ids), time() + 2592000, '/');
}

function recently_viewed_ids(int $exclude = 0): array
{
    $ids = array_filter(array_map('intval', explode(',', $_COOKIE['recently_viewed'] ?? '')));
    return array_values(array_filter($ids, fn($id) => $id !== $exclude));
}

/* =========================================================================
 |  Rendu d'étoiles de notation
 * ========================================================================= */

function star_rating(float $rating, bool $showValue = false): string
{
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '<span class="stars" aria-label="' . number_format($rating, 1) . ' sur 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $html .= '<svg class="star full" viewBox="0 0 24 24"><path d="M12 2l3 6.5 7 .8-5.2 4.7 1.5 7L12 17.8 5.2 21l1.5-7L1.5 9.3l7-.8z"/></svg>';
        } elseif ($half && $i === $full + 1) {
            $html .= '<svg class="star half" viewBox="0 0 24 24"><defs><linearGradient id="hg' . $i . '"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="transparent"/></linearGradient></defs><path fill="url(#hg' . $i . ')" d="M12 2l3 6.5 7 .8-5.2 4.7 1.5 7L12 17.8 5.2 21l1.5-7L1.5 9.3l7-.8z"/></svg>';
        } else {
            $html .= '<svg class="star empty" viewBox="0 0 24 24"><path d="M12 2l3 6.5 7 .8-5.2 4.7 1.5 7L12 17.8 5.2 21l1.5-7L1.5 9.3l7-.8z"/></svg>';
        }
    }
    if ($showValue) {
        $html .= '<span class="stars-value">' . number_format($rating, 1) . '</span>';
    }
    $html .= '</span>';
    return $html;
}

/* =========================================================================
 |  SEO
 * ========================================================================= */

function meta_defaults(): array
{
    return [
        'title'       => setting('site_name', 'La Bibliothèque Numérique') . ' · ' . setting('site_tagline', 'Des livres et des présentations, partout avec vous.'),
        'description' => setting('meta_description', 'Livres et présentations numériques (PDF et PowerPoint) : aperçu gratuit, achat sécurisé par BaridiMob, lecture en ligne immédiate.'),
        'image'       => asset('images/og-image.svg'),
    ];
}

/* =========================================================================
 |  Accès aux livres (achat / lecture)
 * ========================================================================= */

/** L'utilisateur courant possède-t-il l'accès complet à ce livre ? */
function has_book_access(int $bookId): bool
{
    $u = current_user();
    if (!$u) { return false; }
    static $owned = null;
    if ($owned === null) {
        $rows = Database::all('SELECT book_id FROM book_access WHERE customer_id = ?', [$u['id']]);
        $owned = array_map('intval', array_column($rows, 'book_id'));
    }
    return in_array($bookId, $owned, true);
}

/** Libellé du prix d'un livre (gère la gratuité). */
function book_price_label(array $b): string
{
    return ((float) $b['price'] <= 0) ? 'Gratuit' : money($b['price']);
}

/* =========================================================================
 |  Formats de fichier (PDF ou présentation PowerPoint)
 * ========================================================================= */

/** Format d'un titre : 'pdf' (défaut) ou 'pptx'. */
function book_format(array $b): string
{
    $type = strtolower(trim((string) ($b['file_type'] ?? '')));
    if ($type === '') {
        // Anciennes fiches sans colonne file_type : on déduit de l'extension.
        $type = strtolower(pathinfo((string) ($b['pdf_file'] ?? ''), PATHINFO_EXTENSION));
    }
    return in_array($type, ['pptx', 'ppt'], true) ? 'pptx' : 'pdf';
}

/** Le titre est-il une présentation (diapositives) ? */
function book_is_slides(array $b): bool
{
    return book_format($b) === 'pptx';
}

/** « pages » ou « diapositives » selon le format. */
function book_unit(array $b, bool $plural = true): string
{
    if (book_is_slides($b)) { return $plural ? 'diapositives' : 'diapositive'; }
    return $plural ? 'pages' : 'page';
}

/** Libellé public du format (« PDF » / « PowerPoint »). */
function book_format_label(array $b): string
{
    return book_is_slides($b) ? 'PowerPoint' : 'PDF';
}

/** Extensions acceptées à l'upload, par format. */
function book_allowed_extensions(): array
{
    return ['pdf' => ['pdf'], 'pptx' => ['pptx', 'ppt']];
}
