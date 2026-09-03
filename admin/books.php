<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

if (is_post()) {
    csrf_check();
    if (input('action') === 'delete') {
        Database::run('UPDATE books SET deleted_at = ' . (Database::driver()==='sqlite'?"datetime('now')":'NOW()') . ' WHERE id = ?', [(int) input('id')]);
        flash('Titre supprimé.');
    }
    redirect('admin/books.php');
}

$q = input('q');
$where = 'b.deleted_at IS NULL'; $params = [];
if ($q) { $where .= ' AND (b.title LIKE ? OR b.author LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$perPage = 15;
$total = (int) Database::scalar("SELECT COUNT(*) FROM books b WHERE $where", $params);
$pg = paginate($total, $perPage, (int) input('page', '1'));
$books = Database::all("SELECT b.*, c.name cat_name FROM books b LEFT JOIN categories c ON c.id=b.category_id WHERE $where ORDER BY b.id DESC LIMIT $perPage OFFSET {$pg['offset']}", $params);

$adminActive = 'books';
$adminTitle = 'Livres & présentations';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-panel">
  <div class="a-panel-head">
    <form method="get" class="a-search" style="background:var(--a-bg)"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="Rechercher un titre…"></form>
    <a href="<?php h(url('admin/book-edit.php')); ?>" class="a-btn"><?= icon('plus') ?> Ajouter un titre</a>
  </div>
  <div class="a-table-wrap"><table class="a-table">
    <thead><tr><th>Titre</th><th>Rayon</th><th>Prix</th><th>Format</th><th>Pages / Aperçu</th><th>Fichier</th><th>État</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($books as $b):
      $hasFile = $b['pdf_file'] && is_file(UPLOADS_PATH . '/pdf/' . basename($b['pdf_file'])); ?>
      <tr>
        <td><div class="a-prodcell"><img class="thumb" src="<?php h(upload_url($b['cover_image'] ?? null, 'covers', $b['title'])); ?>" alt=""><div><strong><?php h(excerpt($b['title'], 32)); ?></strong><br><span class="a-tag"><?php h($b['author']); ?></span></div></div></td>
        <td><?php h($b['cat_name'] ?? '-'); ?></td>
        <td><strong><?= e(book_price_label($b)) ?></strong><?php if ($b['old_price'] > $b['price']): ?><br><span class="a-tag" style="text-decoration:line-through"><?= money($b['old_price']) ?></span><?php endif; ?></td>
        <td><span class="a-tag"><?= e(book_format_label($b)) ?></span></td>
        <td><?= (int) $b['pages_count'] ?> <?= book_is_slides($b) ? 'dia.' : 'p.' ?> / <?= (int) $b['preview_pages'] ?> aperçu</td>
        <td><?php if ($hasFile): ?><span class="a-pill st-paid"><?= icon('check') ?> OK</span><?php else: ?><span class="a-pill st-cancelled">Manquant</span><?php endif; ?></td>
        <td><?php if ($b['on_sale']): ?><span class="a-pill st-cancelled" style="background:#f2d6d2;color:#a3302a">Promo</span><?php elseif ($b['is_new']): ?><span class="a-pill st-awaiting">Nouveau</span><?php else: ?><span class="a-pill st-paid">Actif</span><?php endif; ?></td>
        <td style="white-space:nowrap">
          <a class="a-iconlink" href="<?php h(url('livre.php?slug=' . $b['slug'])); ?>" target="_blank" title="Voir"><?= icon('eye') ?></a>
          <a class="a-iconlink" href="<?php h(url('admin/book-edit.php?id=' . $b['id'])); ?>" title="Modifier"><?= icon('edit') ?></a>
          <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce titre ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="a-iconlink" style="border:0;background:none;cursor:pointer" title="Supprimer"><?= icon('trash') ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php require INCLUDES_PATH . '/admin_pagination.php'; ?>
</div>
<?php admin_footer(); ?>
