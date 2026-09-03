<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$cat = input('cat');
$where = "status='published'"; $params = [];
if ($cat) { $where .= ' AND category = ?'; $params[] = $cat; }
$posts = Database::all("SELECT * FROM blog_posts WHERE $where ORDER BY published_at DESC", $params);
$cats = Database::all("SELECT category, COUNT(*) c FROM blog_posts WHERE status='published' GROUP BY category ORDER BY c DESC");
$featured = $posts[0] ?? null; $rest = array_slice($posts, 1);
$pageTitle = 'Le carnet de notre équipe';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Le carnet']]) ?></div>
<section class="section-sm"><div class="container">
  <div class="section-head"><span class="eyebrow">Le carnet</span><h1>Conseils &amp; actualités</h1><p>Nos articles pour tirer le meilleur de vos lectures et de vos présentations.</p><div class="divider-orn"></div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:32px">
    <a href="<?php h(url('blog.php')); ?>" class="chip" style="<?= !$cat?'background:var(--grad);color:#f8f2e4':'' ?>">Tout</a>
    <?php foreach ($cats as $c): ?><a href="<?php h(url('blog.php?cat=' . urlencode($c['category']))); ?>" class="chip" style="<?= $cat===$c['category']?'background:var(--grad);color:#f8f2e4':'' ?>"><?php h($c['category']); ?> (<?= $c['c'] ?>)</a><?php endforeach; ?>
  </div>
  <?php if ($featured && !$cat): ?>
  <a href="<?php h(url('blog-post.php?slug=' . $featured['slug'])); ?>" class="blog-card reveal" style="display:grid;grid-template-columns:1.3fr 1fr;margin-bottom:32px">
    <div class="bl-media" style="aspect-ratio:auto"><img src="<?php h(upload_url($featured['image'] ?? null, 'blog', $featured['title'])); ?>" alt="<?php h($featured['title']); ?>"></div>
    <div class="bl-body" style="justify-content:center;padding:36px"><span class="bl-cat"><?php h($featured['category']); ?> · À la une</span><h2 style="font-size:1.8rem"><?php h($featured['title']); ?></h2><p style="color:var(--muted)"><?php h(excerpt($featured['excerpt'], 150)); ?></p><div class="bl-meta"><span><?= icon('user') ?> <?php h($featured['author']); ?></span><span><?= icon('clock') ?> <?php h(fdate($featured['published_at'])); ?></span></div></div>
  </a>
  <?php endif; ?>
  <div class="blog-grid">
    <?php foreach ($rest as $post): ?>
    <a href="<?php h(url('blog-post.php?slug=' . $post['slug'])); ?>" class="blog-card reveal">
      <div class="bl-media"><img src="<?php h(upload_url($post['image'] ?? null, 'blog', $post['title'])); ?>" alt="<?php h($post['title']); ?>" loading="lazy"></div>
      <div class="bl-body"><span class="bl-cat"><?php h($post['category']); ?></span><h3><?php h($post['title']); ?></h3><p style="color:var(--muted);font-size:.9rem"><?php h(excerpt($post['excerpt'], 90)); ?></p><div class="bl-meta"><span><?= icon('clock') ?> <?php h(fdate($post['published_at'])); ?></span><span><?= icon('eye') ?> <?php h($post['views']); ?></span></div></div>
    </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
