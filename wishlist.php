<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

// Fusionne favoris BDD (si connecté) et session.
$ids = $_SESSION['wishlist'] ?? [];
if ($u = current_user()) {
    $db = Database::all('SELECT product_id FROM wishlists WHERE customer_id=?', [$u['id']]);
    $ids = array_values(array_unique(array_merge($ids, array_column($db, 'product_id'))));
    $_SESSION['wishlist'] = $ids;
}
$products = products_by_ids($ids);

$pageTitle = 'Mes favoris';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Mes favoris']]) ?></div>
<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:6px">Mes favoris</h1>
    <p style="color:var(--muted);margin-bottom:24px"><?= count($products) ?> produit(s) sauvegardé(s)</p>
    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="ic"><?= icon('heart') ?></div>
        <h3>Votre liste de favoris est vide</h3>
        <p style="color:var(--muted)">Cliquez sur le cœur des produits pour les retrouver ici.</p>
        <a href="<?php h(url('shop.php')); ?>" class="btn btn-lg">Découvrir la boutique</a>
      </div>
    <?php else: ?>
      <div class="product-grid"><?php foreach ($products as $p) { echo product_card($p); } ?></div>
    <?php endif; ?>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
