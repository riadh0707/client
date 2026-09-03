<?php
/** api/cart.php — Panier de livres (1 exemplaire par livre). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['action'] ?? '';
if (is_post()) { csrf_check(); }

$cart = $_SESSION['cart'] ?? [];

switch ($action) {
    case 'add':
        $id = (int) ($body['id'] ?? 0);
        $b = book_by_id($id);
        if (!$b) { json_response(['ok' => false, 'error' => 'Livre introuvable.'], 404); }
        if (has_book_access($id)) { json_response(['ok' => false, 'error' => 'Vous possédez déjà ce livre.']); }
        $cart[$id] = 1;
        break;
    case 'remove':
        unset($cart[(int) ($body['id'] ?? 0)]);
        break;
    default:
        json_response(['ok' => false, 'error' => 'Action inconnue.'], 400);
}
$_SESSION['cart'] = $cart;

$books = books_by_ids(array_keys($cart));
$subtotal = 0;
foreach ($books as $b) { $subtotal += (float) $b['price']; }
$discount = (float) ($_SESSION['coupon']['amount'] ?? 0);
$discount = min($discount, $subtotal);

json_response([
    'ok'       => true,
    'count'    => count($cart),
    'message'  => $action === 'add' ? 'Livre ajouté au panier.' : 'Panier mis à jour.',
    'subtotal' => money($subtotal),
    'subtotalRaw' => $subtotal,
    'discount' => money($discount),
    'total'    => money(max(0, $subtotal - $discount)),
]);
