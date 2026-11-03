<?php
/** api/search.php — Suggestions de recherche instantanée (JSON). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) { json_response(['ok' => true, 'items' => [], 'base' => BASE_URL]); }

$res = search_products(['q' => $q, 'perPage' => 6, 'sort' => 'popular']);
$items = array_map(fn($p) => [
    'slug'  => $p['slug'],
    'name'  => $p['name'],
    'brand' => $p['brand_name'] ?? '',
    'price' => money($p['price']),
    'image' => upload_url($p['primary_image'] ?? null, 'products', $p['name']),
], $res['items']);

json_response(['ok' => true, 'items' => $items, 'base' => BASE_URL]);
