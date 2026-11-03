<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_post()) { redirect('shop.php'); }
csrf_check();

$productId = (int) input('product_id');
$name = mb_substr(input('author_name'), 0, 120);
$rating = max(1, min(5, (int) input('rating')));
$title = mb_substr(input('title'), 0, 160);
$body = mb_substr(input('body'), 0, 2000);

if (!rate_limit('review', 4, 120)) {
    flash('Trop d\'avis envoyés, réessayez plus tard.', 'error');
} elseif ($productId && $name && $body) {
    Database::run(
        'INSERT INTO reviews (product_id, customer_id, author_name, rating, title, body, status) VALUES (?,?,?,?,?,?,?)',
        [$productId, current_user()['id'] ?? null, $name, $rating, $title, $body, 'pending']
    );
    flash('Merci ! Votre avis sera publié après modération.', 'success');
} else {
    flash('Veuillez remplir tous les champs requis.', 'error');
}

$p = Database::first('SELECT slug FROM products WHERE id = ?', [$productId]);
redirect('product.php?slug=' . ($p['slug'] ?? ''));
