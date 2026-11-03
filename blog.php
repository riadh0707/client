<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';

$cat = input('cat');
$where = "status = 'published'"; $params = [];
if ($cat) { $where .= ' AND category = ?'; $params[] = $cat; }
$posts = Database::all("SELECT * FROM blog_posts WHERE $where ORDER BY published_at DESC", $params);
$cats = Database::all("SELECT category, COUNT(*) c FROM blog_posts WHERE status='published' GROUP BY category ORDER BY c DESC");
$featured = $posts[0] ?? null;
$rest = array_slice($posts, 1);

$pageTitle = 'Blog beauté';
$pageDescription = 'Conseils beauté, tutoriels maquillage, routines soins et tendances par les experts Bella Rosa.';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Blog']]) ?></div>
<section class="section-sm">
  <div class="container">
    <div class="page-hero" style="background-image:url('<?php h(upload_url('banner-04.jpg', 'banners')); ?>')"><div><h1>Conseils, tutoriels & tendances</h1><p>Nos expertes partagent leurs secrets pour sublimer votre beauté au quotidien.</p></div></div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:34px">
      <a href="<?php h(url('blog.php')); ?>" class="chip<?= !$cat ? ' on' : '' ?>">Tout</a>
      <?php foreach ($cats as $c): ?>
      <a href="<?php h(url('blog.php?cat=' . urlencode($c['category']))); ?>" class="chip<?= $cat === $c['category'] ? ' on' : '' ?>"><?php h($c['category']); ?> (<?= $c['c'] ?>)</a>
      <?php endforeach; ?>
    </div>

    <?php if ($featured && !$cat): ?>
    <a href="<?php h(url('blog-post.php?slug=' . $featured['slug'])); ?>" class="blog-card reveal" style="display:grid;grid-template-columns:1.3fr 1fr;margin-bottom:34px">
      <div class="bc-media" style="aspect-ratio:auto"><img src="<?php h(upload_url($featured['image'] ?? null, 'blog', $featured['title'])); ?>" alt="<?php h($featured['title']); ?>"></div>
      <div class="bc-body" style="justify-content:center;padding:38px">
        <span class="bc-cat"><?php h($featured['category']); ?> · À la une</span>
        <h2 style="font-size:1.8rem"><?php h($featured['title']); ?></h2>
        <p style="color:var(--muted)"><?php h(excerpt($featured['excerpt'], 160)); ?></p>
        <div class="bc-meta"><span><?= icon('user') ?> <?php h($featured['author']); ?></span><span><?= icon('clock') ?> <?php h(fdate($featured['published_at'])); ?></span></div>
      </div>
    </a>
    <?php endif; ?>

    <div class="blog-grid">
      <?php foreach ($rest as $post): ?>
      <a href="<?php h(url('blog-post.php?slug=' . $post['slug'])); ?>" class="blog-card reveal">
        <div class="bc-media"><img src="<?php h(upload_url($post['image'] ?? null, 'blog', $post['title'])); ?>" alt="<?php h($post['title']); ?>" loading="lazy"></div>
        <div class="bc-body">
          <span class="bc-cat"><?php h($post['category']); ?></span>
          <h3><?php h($post['title']); ?></h3>
          <p style="color:var(--muted);font-size:.9rem"><?php h(excerpt($post['excerpt'], 90)); ?></p>
          <div class="bc-meta"><span><?= icon('clock') ?> <?php h(fdate($post['published_at'])); ?></span><span><?= icon('eye') ?> <?php h($post['views']); ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
