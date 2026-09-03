<?php
/** api/coupon.php - Application d'un code promo au panier. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (is_post()) { csrf_check(); }
$code = strtoupper(trim((string) ($body['code'] ?? '')));
if ($code === '') { unset($_SESSION['coupon']); json_response(['ok' => true, 'message' => 'Code retiré.']); }
$c = Database::first('SELECT * FROM coupons WHERE code=? AND active=1', [$code]);
if (!$c) { json_response(['ok' => false, 'message' => 'Code promo invalide.']); }
if ($c['expires_at'] && $c['expires_at'] < date('Y-m-d')) { json_response(['ok' => false, 'message' => 'Ce code a expiré.']); }
$books = books_by_ids(array_keys($_SESSION['cart'] ?? []));
$subtotal = 0; foreach ($books as $b) { $subtotal += (float) $b['price']; }
if ($subtotal < $c['min_amount']) { json_response(['ok' => false, 'message' => 'Minimum ' . money($c['min_amount']) . ' d\'achat requis.']); }
$amount = $c['type'] === 'percent' ? round($subtotal * $c['value'] / 100) : (float) $c['value'];
$amount = min($amount, $subtotal);
$_SESSION['coupon'] = ['code' => $code, 'amount' => $amount];
json_response(['ok' => true, 'message' => 'Code appliqué : -' . money($amount) . ' !']);
