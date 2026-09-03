<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();
if (is_post()) {
    csrf_check();
    if (input('action') === 'save') {
        $cid = (int) input('id');
        $data = [input('name'), slugify(input('name')) . ($cid ? '' : '-' . substr(bin2hex(random_bytes(2)),0,3)), input('description'), input('seo_title'), (int) input('position')];
        if ($cid) { Database::run('UPDATE categories SET name=?, slug=?, description=?, seo_title=?, position=? WHERE id=?', array_merge($data, [$cid])); flash('Rayon mis à jour.'); }
        else { Database::run('INSERT INTO categories (name, slug, description, seo_title, position) VALUES (?,?,?,?,?)', $data); flash('Rayon créé.'); }
    } elseif (input('action') === 'delete') {
        Database::run('UPDATE categories SET deleted_at = ' . (Database::driver()==='sqlite'?"datetime('now')":'NOW()') . ' WHERE id = ?', [(int) input('id')]);
        flash('Rayon supprimé.');
    }
    redirect('admin/categories.php');
}
$edit = (int) input('edit') ? Database::first('SELECT * FROM categories WHERE id = ?', [(int) input('edit')]) : null;
$cats = Database::all('SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category_id=c.id AND b.deleted_at IS NULL) bcount FROM categories c WHERE c.deleted_at IS NULL ORDER BY c.position');
$adminActive = 'categories'; $adminTitle = 'Rayons';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-grid-2">
  <div class="a-panel"><div class="a-panel-head"><h3>Les rayons</h3></div>
    <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Nom</th><th>Livres</th><th></th></tr></thead><tbody>
    <?php foreach ($cats as $c): ?><tr><td><strong><?php h($c['name']); ?></strong></td><td><span class="a-tag"><?= (int) $c['bcount'] ?></span></td>
      <td style="white-space:nowrap"><a class="a-iconlink" href="?edit=<?= $c['id'] ?>"><?= icon('edit') ?></a>
      <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="a-iconlink" style="border:0;background:none;cursor:pointer"><?= icon('trash') ?></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="a-panel"><h3 style="margin-bottom:16px"><?= $edit ? 'Modifier le rayon' : 'Nouveau rayon' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="a-field"><label>Nom *</label><input name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div class="a-field"><label>Description</label><textarea name="description" style="min-height:70px"><?= e($edit['description'] ?? '') ?></textarea></div>
      <div class="a-row"><div class="a-field"><label>Titre SEO</label><input name="seo_title" value="<?= e($edit['seo_title'] ?? '') ?>"></div><div class="a-field"><label>Position</label><input type="number" name="position" value="<?= e($edit['position'] ?? '0') ?>"></div></div>
      <button class="a-btn"><?= icon('check') ?> Enregistrer</button>
      <?php if ($edit): ?><a href="<?php h(url('admin/categories.php')); ?>" class="a-btn ghost">Annuler</a><?php endif; ?>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
