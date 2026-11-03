<?php
/** api/cart.php — Gestion du panier (JSON). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['action'] ?? '';

if (is_post()) { csrf_check(); }

$cart = $_SESSION['cart'] ?? [];

switch ($action) {
    case 'add':
        $id  = (int) ($body['id'] ?? 0);
        $qty = max(1, (int) ($body['qty'] ?? 1));
        $p = product_by_id($id);
        if (!$p) { json_response(['ok' => false, 'error' => 'Produit introuvable.'], 404); }
        if ((int) $p['stock'] <= 0) { json_response(['ok' => false, 'error' => 'Produit épuisé.']); }
        $cart[$id]['qty'] = min((int) $p['stock'], ($cart[$id]['qty'] ?? 0) + $qty);
        break;

    case 'update':
        $id  = (int) ($body['id'] ?? 0);
        $qty = (int) ($body['qty'] ?? 1);
        if ($qty <= 0) { unset($cart[$id]); }
        else {
            $p = product_by_id($id);
            $cart[$id]['qty'] = $p ? min((int) $p['stock'], $qty) : $qty;
        }
        break;

    case 'remove':
        unset($cart[(int) ($body['id'] ?? 0)]);
        break;

    default:
        json_response(['ok' => false, 'error' => 'Action inconnue.'], 400);
}

$_SESSION['cart'] = $cart;

// Recalcul du récapitulatif.
$ids = array_keys($cart);
$products = products_by_ids($ids);
$subtotal = 0; $count = 0; $lines = [];
foreach ($products as $p) {
    $qty = $cart[$p['id']]['qty'];
    $lineTotal = $p['price'] * $qty;
    $subtotal += $lineTotal; $count += $qty;
    $lines[$p['id']] = [
        'qty' => $qty,
        'lineTotal' => money($lineTotal),
        'lineTotalRaw' => $lineTotal,
    ];
}
$freeMin  = (float) setting('free_shipping_min', 8000);
$shipping = ($subtotal >= $freeMin || $subtotal == 0) ? 0 : (float) setting('shipping_flat', 600);

json_response([
    'ok'        => true,
    'count'     => $count,
    'message'   => $action === 'add' ? 'Produit ajouté au panier.' : 'Panier mis à jour.',
    'subtotal'  => money($subtotal),
    'subtotalRaw' => $subtotal,
    'shipping'  => $shipping == 0 ? 'Offerte' : money($shipping),
    'shippingRaw' => $shipping,
    'total'     => money($subtotal + $shipping),
    'freeMin'   => $freeMin,
    'freeProgress' => $freeMin > 0 ? min(100, round($subtotal / $freeMin * 100)) : 100,
    'lines'     => $lines,
]);
