<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!is_post()) { redirect('catalogue.php'); }
csrf_check();
$bookId = (int) input('book_id');
$name = mb_substr(input('author_name'), 0, 120);
$rating = max(1, min(5, (int) input('rating')));
$title = mb_substr(input('title'), 0, 160);
$body = mb_substr(input('body'), 0, 2000);
if (!rate_limit('review', 4, 120)) { flash('Trop d\'avis envoyés, réessayez plus tard.', 'error'); }
elseif ($bookId && $name && $body) {
    Database::run('INSERT INTO reviews (book_id, customer_id, author_name, rating, title, body, status) VALUES (?,?,?,?,?,?,?)',
        [$bookId, current_user()['id'] ?? null, $name, $rating, $title, $body, 'pending']);
    flash('Merci ! Votre avis sera publié après validation.', 'success');
} else { flash('Veuillez remplir tous les champs requis.', 'error'); }
$b = Database::first('SELECT slug FROM books WHERE id = ?', [$bookId]);
redirect('livre.php?slug=' . ($b['slug'] ?? ''));
