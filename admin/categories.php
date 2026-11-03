<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

if (is_post()) {
    csrf_check();
    $action = input('action');
    if ($action === 'save') {
        $id = (int) input('id');
        $data = [input('name'), slugify(input('name')) . ($id ? '' : '-' . substr(bin2hex(random_bytes(2)),0,3)),
                 (int) input('parent_id') ?: null, input('description'), input('seo_title'), (int) input('position')];
        if ($id) {
            Database::run('UPDATE categories SET name=?, slug=?, parent_id=?, description=?, seo_title=?, position=? WHERE id=?', array_merge($data, [$id]));
            flash('Catégorie mise à jour.');
        } else {
            Database::run('INSERT INTO categories (name, slug, parent_id, description, seo_title, position) VALUES (?,?,?,?,?,?)', $data);
            flash('Catégorie créée.');
        }
    } elseif ($action === 'delete') {
        Database::run('UPDATE categories SET deleted_at = ' . (Database::driver()==='sqlite'?"datetime('now')":'NOW()') . ' WHERE id = ?', [(int) input('id')]);
        flash('Catégorie supprimée.');
    }
    redirect('admin/categories.php');
}

$edit = (int) input('edit') ? Database::first('SELECT * FROM categories WHERE id = ?', [(int) input('edit')]) : null;
$cats = Database::all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.deleted_at IS NULL) pcount FROM categories c WHERE c.deleted_at IS NULL ORDER BY c.position');
$parents = array_filter($cats, fn($c) => $c['parent_id'] === null);

$adminActive = 'categories';
$adminTitle = 'Catégories';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-grid-2">
  <div class="a-panel">
    <div class="a-panel-head"><h3>Arborescence</h3></div>
    <div class="a-table-wrap"><table class="a-table">
      <thead><tr><th>Nom</th><th>Parent</th><th>Produits</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c):
        $parentName = $c['parent_id'] ? (array_values(array_filter($cats, fn($x)=>$x['id']==$c['parent_id']))[0]['name'] ?? '') : ''; ?>
        <tr>
          <td><?= $c['parent_id'] ? '<span style="color:var(--a-muted)">— </span>' : '<strong>' ?><?php h($c['name']); ?><?= $c['parent_id'] ? '' : '</strong>' ?></td>
          <td><?php h($parentName ?: '—'); ?></td>
          <td><span class="a-tag"><?= (int) $c['pcount'] ?></span></td>
          <td style="white-space:nowrap">
            <a class="a-iconlink" href="?edit=<?= $c['id'] ?>"><?= icon('edit') ?></a>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="a-iconlink" style="border:0;background:none;cursor:pointer"><?= icon('trash') ?></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="a-panel">
    <h3 style="margin-bottom:16px"><?= $edit ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h3>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="a-field"><label>Nom *</label><input name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div class="a-field"><label>Catégorie parente</label>
        <select name="parent_id"><option value="">— Aucune (racine)</option>
          <?php foreach ($parents as $pc): if (($edit['id'] ?? 0) == $pc['id']) continue; ?>
            <option value="<?= $pc['id'] ?>" <?= ($edit['parent_id'] ?? 0) == $pc['id'] ? 'selected' : '' ?>><?php h($pc['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="a-field"><label>Description</label><textarea name="description" style="min-height:70px"><?= e($edit['description'] ?? '') ?></textarea></div>
      <div class="a-row">
        <div class="a-field"><label>Titre SEO</label><input name="seo_title" value="<?= e($edit['seo_title'] ?? '') ?>"></div>
        <div class="a-field"><label>Position</label><input type="number" name="position" value="<?= e($edit['position'] ?? '0') ?>"></div>
      </div>
      <button class="a-btn"><?= icon('check') ?> Enregistrer</button>
      <?php if ($edit): ?><a href="<?php h(url('admin/categories.php')); ?>" class="a-btn ghost">Annuler</a><?php endif; ?>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
