<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
$ids = $_SESSION['wishlist'] ?? [];
if ($u = current_user()) {
    $db = Database::all('SELECT book_id FROM wishlists WHERE customer_id=?', [$u['id']]);
    $ids = array_values(array_unique(array_merge($ids, array_column($db, 'book_id'))));
    $_SESSION['wishlist'] = $ids;
}
$books = books_by_ids($ids);
$pageTitle = 'Ma liste de lecture';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Ma liste de lecture']]) ?></div>
<section class="section-sm"><div class="container">
  <h1 style="margin-bottom:6px">Ma liste de lecture</h1>
  <p style="color:var(--muted);margin-bottom:24px"><?= count($books) ?> livre(s) sauvegardé(s)</p>
  <?php if (empty($books)): ?>
    <div class="empty-state"><div class="ic"><?= icon('bookmark') ?></div><h3>Votre liste est vide</h3><p style="color:var(--muted)">Cliquez sur le marque-page des livres pour les retrouver ici.</p><a href="<?php h(url('catalogue.php')); ?>" class="btn btn-lg">Découvrir le catalogue</a></div>
  <?php else: ?><div class="book-grid"><?php foreach ($books as $b) { echo book_card($b); } ?></div><?php endif; ?>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
