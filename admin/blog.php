<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();
if (is_post()) {
    csrf_check();
    if (input('action') === 'save') {
        $bid = (int) input('id');
        $data = [input('title'), input('category'), input('excerpt'), input('body'), input('author'), input('tags'), input('status', 'published')];
        if ($bid) { Database::run('UPDATE blog_posts SET title=?, category=?, excerpt=?, body=?, author=?, tags=?, status=? WHERE id=?', array_merge($data, [$bid])); flash('Article mis à jour.'); }
        else { $slug = slugify(input('title')) . '-' . substr(bin2hex(random_bytes(2)),0,3); Database::run('INSERT INTO blog_posts (title, category, excerpt, body, author, tags, status, slug, published_at) VALUES (?,?,?,?,?,?,?,?,' . (Database::driver()==='sqlite'?"datetime('now')":'NOW()') . ')', array_merge($data, [$slug])); flash('Article publié.'); }
    } elseif (input('action') === 'delete') {
        Database::run('UPDATE blog_posts SET deleted_at = ' . (Database::driver()==='sqlite'?"datetime('now')":'NOW()') . ' WHERE id = ?', [(int) input('id')]); flash('Article supprimé.');
    }
    redirect('admin/blog.php');
}
$edit = (int) input('edit') ? Database::first('SELECT * FROM blog_posts WHERE id = ?', [(int) input('edit')]) : null;
$new = input('new') !== '';
$posts = Database::all("SELECT * FROM blog_posts WHERE deleted_at IS NULL ORDER BY published_at DESC");
$adminActive = 'blog'; $adminTitle = 'Le carnet';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<?php if ($edit || $new): ?>
  <a href="<?php h(url('admin/blog.php')); ?>" class="a-btn ghost sm" style="margin-bottom:16px"><?= icon('arrow','flip') ?> Retour</a>
  <div class="a-panel" style="max-width:820px"><h3 style="margin-bottom:16px"><?= $edit ? 'Modifier' : 'Nouvel article' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="a-field"><label>Titre *</label><input name="title" required value="<?= e($edit['title'] ?? '') ?>"></div>
      <div class="a-row"><div class="a-field"><label>Catégorie</label><input name="category" value="<?= e($edit['category'] ?? 'Conseils') ?>"></div><div class="a-field"><label>Auteur</label><input name="author" value="<?= e($edit['author'] ?? 'La Bibliothèque') ?>"></div></div>
      <div class="a-field"><label>Extrait</label><textarea name="excerpt" style="min-height:60px"><?= e($edit['excerpt'] ?? '') ?></textarea></div>
      <div class="a-field"><label>Contenu (HTML autorisé)</label><textarea name="body" style="min-height:220px"><?= e($edit['body'] ?? '') ?></textarea></div>
      <div class="a-row"><div class="a-field"><label>Tags (virgules)</label><input name="tags" value="<?= e($edit['tags'] ?? '') ?>"></div><div class="a-field"><label>Statut</label><select name="status"><option value="published" <?= ($edit['status']??'')==='published'?'selected':'' ?>>Publié</option><option value="draft" <?= ($edit['status']??'')==='draft'?'selected':'' ?>>Brouillon</option></select></div></div>
      <button class="a-btn"><?= icon('check') ?> Enregistrer</button>
    </form>
  </div>
<?php else: ?>
  <div class="a-panel"><div class="a-panel-head"><h3><?= count($posts) ?> articles</h3><a href="?new=1" class="a-btn"><?= icon('plus') ?> Nouvel article</a></div>
    <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Titre</th><th>Catégorie</th><th>Vues</th><th>Statut</th><th></th></tr></thead><tbody>
    <?php foreach ($posts as $post): ?><tr><td><strong><?php h(excerpt($post['title'], 44)); ?></strong></td><td><span class="a-tag"><?php h($post['category']); ?></span></td><td><?= (int) $post['views'] ?></td><td><span class="a-pill st-<?= $post['status']==='published'?'paid':'pending' ?>"><?php h($post['status']); ?></span></td>
      <td style="white-space:nowrap"><a class="a-iconlink" href="<?php h(url('blog-post.php?slug=' . $post['slug'])); ?>" target="_blank"><?= icon('eye') ?></a><a class="a-iconlink" href="?edit=<?= $post['id'] ?>"><?= icon('edit') ?></a>
      <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $post['id'] ?>"><button class="a-iconlink" style="border:0;background:none;cursor:pointer"><?= icon('trash') ?></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
