<?php
/** api/search.php - Suggestions de recherche instantanée. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) { json_response(['ok' => true, 'items' => [], 'base' => BASE_URL]); }
$res = search_books(['q' => $q, 'perPage' => 6, 'sort' => 'popular']);
$items = array_map(fn($b) => [
    'slug' => $b['slug'], 'name' => $b['title'], 'author' => $b['author'] ?? '',
    'price' => book_price_label($b),
    'image' => upload_url($b['cover_image'] ?? null, 'covers', $b['title']),
], $res['items']);
json_response(['ok' => true, 'items' => $items, 'base' => BASE_URL]);
