<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

// Suppression (soft delete)
if (is_post()) {
    csrf_check();
    if (input('action') === 'delete') {
        Database::run('UPDATE products SET deleted_at = ' . (Database::driver()==='sqlite'?"datetime('now')":'NOW()') . ' WHERE id = ?', [(int) input('id')]);
        flash('Produit supprimé.');
    }
    redirect('admin/products.php');
}

$q = input('q');
$where = 'p.deleted_at IS NULL'; $params = [];
if ($q) { $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

$perPage = 15;
$total = (int) Database::scalar("SELECT COUNT(*) FROM products p WHERE $where", $params);
$pg = paginate($total, $perPage, (int) input('page', '1'));
$products = Database::all(
    "SELECT p.*, b.name brand_name, c.name cat_name FROM products p
     LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id
     WHERE $where ORDER BY p.id DESC LIMIT $perPage OFFSET {$pg['offset']}",
    $params
);

$adminActive = 'products';
$adminTitle = 'Produits';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-panel">
  <div class="a-panel-head">
    <form method="get" class="a-search" style="background:var(--a-bg)"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Rechercher un produit…"></form>
    <a href="<?php h(url('admin/product-edit.php')); ?>" class="a-btn"><?= icon('plus') ?> Nouveau produit</a>
  </div>
  <div class="a-table-wrap"><table class="a-table">
    <thead><tr><th>Produit</th><th>SKU</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>État</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><div class="a-prodcell"><img class="thumb" src="<?php h(upload_url($p['primary_image'] ?? null, 'products', $p['name'])); ?>" alt=""><div><strong><?php h(excerpt($p['name'], 34)); ?></strong><br><span class="a-tag"><?php h($p['brand_name'] ?? '—'); ?></span></div></div></td>
        <td><?php h($p['sku']); ?></td>
        <td><?php h($p['cat_name'] ?? '—'); ?></td>
        <td><strong><?= money($p['price']) ?></strong><?php if ($p['old_price'] > $p['price']): ?><br><span class="a-tag" style="text-decoration:line-through"><?= money($p['old_price']) ?></span><?php endif; ?></td>
        <td><span class="a-tag" style="<?= $p['stock']<5?'background:#fbdcda;color:#b33':($p['stock']<15?'background:#fdf0d8;color:#a06d18':'') ?>"><?= (int) $p['stock'] ?></span></td>
        <td><?php if ($p['on_sale']): ?><span class="a-pill st-cancelled" style="background:#fbdcda;color:#b33">Promo</span><?php elseif ($p['is_new']): ?><span class="a-pill st-confirmed">Nouveau</span><?php else: ?><span class="a-pill st-delivered">Actif</span><?php endif; ?></td>
        <td style="white-space:nowrap">
          <a class="a-iconlink" href="<?php h(url('product.php?slug=' . $p['slug'])); ?>" target="_blank" title="Voir"><?= icon('eye') ?></a>
          <a class="a-iconlink" href="<?php h(url('admin/product-edit.php?id=' . $p['id'])); ?>" title="Modifier"><?= icon('edit') ?></a>
          <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce produit ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="a-iconlink" style="border:0;background:none;cursor:pointer" title="Supprimer"><?= icon('trash') ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php require INCLUDES_PATH . '/admin_pagination.php'; ?>
</div>
<?php admin_footer(); ?>
