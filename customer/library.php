<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_login();
$u = current_user();

$books = Database::all(
    "SELECT b.* FROM book_access a JOIN books b ON b.id = a.book_id
     WHERE a.customer_id = ? AND b.deleted_at IS NULL ORDER BY a.created_at DESC",
    [$u['id']]
);
$orderCount = (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE customer_id = ?', [$u['id']]);
$pendingCount = (int) Database::scalar("SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status IN ('pending','awaiting')", [$u['id']]);

$pageTitle = 'Ma bibliothèque';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm"><div class="container">
  <div class="account-layout">
    <?php $active = 'library'; require INCLUDES_PATH . '/account_nav.php'; ?>
    <div>
      <div class="card-panel" style="background:var(--grad);color:#f4e9d3;margin-bottom:22px">
        <h1 style="color:#f8f2e4;margin-bottom:4px">Bonjour, <?php h($u['first_name']); ?> 👋</h1>
        <p style="color:rgba(244,233,211,.85);margin:0"><?= count($books) ?> titre(s) dans votre bibliothèque · membre depuis <?php h(fdate($u['created_at'], 'F Y')); ?></p>
      </div>

      <div class="stat-cards">
        <div class="stat-card"><div class="sv"><?= count($books) ?></div><div class="sl">Titres acquis</div></div>
        <div class="stat-card"><div class="sv"><?= $orderCount ?></div><div class="sl">Commandes</div></div>
        <div class="stat-card"><div class="sv"><?= $pendingCount ?></div><div class="sl">En validation</div></div>
      </div>

      <div class="card-panel">
        <h3 style="margin-bottom:16px"><?= icon('library') ?> Mes livres &amp; présentations</h3>
        <?php if (empty($books)): ?>
          <div class="empty-state" style="padding:40px"><div class="ic"><?= icon('book') ?></div><h3>Votre bibliothèque est vide</h3><p style="color:var(--muted)">Achetez un titre pour commencer à lire.</p><a href="<?php h(url('catalogue.php')); ?>" class="btn">Découvrir le catalogue</a></div>
        <?php else: ?>
          <div class="lib-grid">
            <?php foreach ($books as $b): ?>
            <div class="lib-book">
              <a href="<?php h(url('lire.php?slug=' . $b['slug'])); ?>" class="cov">
                <img src="<?php h(upload_url($b['cover_image'] ?? null, 'covers', $b['title'])); ?>" alt="<?php h($b['title']); ?>">
                <span class="read"><span class="btn btn-sm"><?= icon('book-open') ?> Lire</span></span>
              </a>
              <strong style="font-family:var(--font-display);font-size:1rem;display:block"><?php h(excerpt($b['title'], 34)); ?></strong>
              <span style="color:var(--muted);font-size:.8rem"><?= (int) $b['pages_count'] ?> <?= e(book_unit($b)) ?> · <?= e(book_format_label($b)) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
