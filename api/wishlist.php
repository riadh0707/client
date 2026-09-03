<?php
/** api/wishlist.php — Liste de lecture (favoris). */
require_once __DIR__ . '/../includes/bootstrap.php';
$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (is_post()) { csrf_check(); }
$id = (int) ($body['id'] ?? 0);
$wish = $_SESSION['wishlist'] ?? [];
$active = false;
if (in_array($id, $wish, true)) {
    $wish = array_values(array_filter($wish, fn($x) => $x !== $id));
    if ($u = current_user()) { Database::run('DELETE FROM wishlists WHERE customer_id=? AND book_id=?', [$u['id'], $id]); }
} else {
    $wish[] = $id; $active = true;
    if ($u = current_user()) { try { Database::run('INSERT INTO wishlists (customer_id, book_id) VALUES (?,?)', [$u['id'], $id]); } catch (Throwable $e) {} }
}
$_SESSION['wishlist'] = $wish;
json_response(['ok' => true, 'active' => $active, 'count' => count($wish)]);
