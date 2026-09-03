<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();
if (is_post()) {
    csrf_check(); $rid = (int) input('id');
    switch (input('action')) {
        case 'approve': Database::run("UPDATE reviews SET status='approved' WHERE id=?", [$rid]); flash('Avis approuvé.'); break;
        case 'reject':  Database::run("UPDATE reviews SET status='rejected' WHERE id=?", [$rid]); flash('Avis rejeté.'); break;
        case 'delete':  Database::run('DELETE FROM reviews WHERE id=?', [$rid]); flash('Avis supprimé.'); break;
    }
    redirect('admin/reviews.php' . (input('f') ? '?status=' . input('f') : ''));
}
$filter = input('status', 'pending');
$where = $filter && $filter !== 'all' ? 'r.status = ?' : '1=1';
$params = $filter && $filter !== 'all' ? [$filter] : [];
$reviews = Database::all("SELECT r.*, b.title btitle, b.slug bslug FROM reviews r JOIN books b ON b.id=r.book_id WHERE $where ORDER BY r.created_at DESC LIMIT 60", $params);
$counts = []; foreach (Database::all('SELECT status, COUNT(*) c FROM reviews GROUP BY status') as $row) { $counts[$row['status']] = $row['c']; }
$adminActive = 'reviews'; $adminTitle = 'Modération des avis';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-panel">
  <div class="a-panel-head"><div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="?status=pending" class="a-btn <?= $filter==='pending'?'':'ghost' ?> sm">En attente (<?= $counts['pending'] ?? 0 ?>)</a>
    <a href="?status=approved" class="a-btn <?= $filter==='approved'?'':'ghost' ?> sm">Approuvés (<?= $counts['approved'] ?? 0 ?>)</a>
    <a href="?status=all" class="a-btn <?= $filter==='all'?'':'ghost' ?> sm">Tous</a>
  </div></div>
  <?php if (empty($reviews)): ?><p class="a-empty">Aucun avis.</p><?php endif; ?>
  <?php foreach ($reviews as $r): ?>
  <div style="border-bottom:1px solid var(--a-border);padding:16px 0;display:flex;gap:14px">
    <span class="a-avatar"><?php h(initials($r['author_name'])); ?></span>
    <div style="flex:1"><div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><strong><?php h($r['author_name']); ?></strong><?= star_rating((float) $r['rating']) ?><span class="a-pill st-<?= $r['status']==='approved'?'paid':($r['status']==='pending'?'pending':'cancelled') ?>"><?php h($r['status']); ?></span></div>
      <div style="color:var(--a-muted);font-size:.82rem;margin:2px 0">sur <a href="<?php h(url('livre.php?slug=' . $r['bslug'])); ?>" target="_blank" style="color:var(--a-primary)"><?php h(excerpt($r['btitle'],40)); ?></a> · <?php h(time_ago($r['created_at'])); ?></div>
      <?php if ($r['title']): ?><strong style="font-size:.9rem"><?php h($r['title']); ?></strong><?php endif; ?>
      <p style="margin:4px 0 0"><?php h($r['body']); ?></p></div>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php if ($r['status'] !== 'approved'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="f" value="<?= e($filter) ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="a-btn sm"><?= icon('check') ?></button></form><?php endif; ?>
      <?php if ($r['status'] !== 'rejected'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="f" value="<?= e($filter) ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="a-btn ghost sm"><?= icon('close') ?></button></form><?php endif; ?>
      <form method="post" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><input type="hidden" name="f" value="<?= e($filter) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="a-btn danger sm"><?= icon('trash') ?></button></form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
