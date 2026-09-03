<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$post = Database::first("SELECT * FROM blog_posts WHERE slug=? AND status='published'", [input('slug')]);
if (!$post) { redirect('blog.php'); }
Database::run('UPDATE blog_posts SET views = views + 1 WHERE id=?', [$post['id']]);
$related = Database::all("SELECT * FROM blog_posts WHERE category=? AND id<>? AND status='published' ORDER BY published_at DESC LIMIT 3", [$post['category'], $post['id']]);
$pageTitle = $post['title']; $pageDescription = excerpt($post['excerpt'], 155); $ogImage = upload_url($post['image'] ?? null, 'blog', $post['title']);
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Le carnet', 'url' => url('blog.php')], ['label' => excerpt($post['title'], 40)]]) ?></div>
<article class="section-sm"><div class="container" style="max-width:820px">
  <span class="bl-cat"><?php h($post['category']); ?></span>
  <h1 style="margin:8px 0 14px"><?php h($post['title']); ?></h1>
  <div class="bd-meta-row" style="margin-bottom:24px"><span class="review-avatar" style="width:34px;height:34px"><?php h(initials($post['author'])); ?></span><span><?php h($post['author']); ?></span><span>· <?php h(fdate($post['published_at'], 'd F Y')); ?></span><span>· <?= icon('eye') ?> <?php h($post['views']); ?> vues</span></div>
  <div class="cov" style="border-radius:12px;overflow:hidden;margin-bottom:28px;aspect-ratio:16/8"><img src="<?php h(upload_url($post['image'] ?? null, 'blog', $post['title'])); ?>" alt="<?php h($post['title']); ?>" style="width:100%;height:100%;object-fit:cover"></div>
  <div class="prose" style="font-size:1.08rem;line-height:1.8"><?= $post['body'] ?></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:26px"><?php foreach (array_filter(array_map('trim', explode(',', $post['tags'] ?? ''))) as $tag): ?><span class="chip"><?= icon('tag') ?> <?php h($tag); ?></span><?php endforeach; ?></div>
</div></article>
<?php if ($related): ?>
<section class="section-sm" style="background:var(--paper-3)"><div class="container"><?= section_head('À lire aussi', 'Articles similaires') ?><div class="blog-grid">
  <?php foreach ($related as $r): ?><a href="<?php h(url('blog-post.php?slug=' . $r['slug'])); ?>" class="blog-card reveal"><div class="bl-media"><img src="<?php h(upload_url($r['image'] ?? null, 'blog', $r['title'])); ?>" alt="<?php h($r['title']); ?>" loading="lazy"></div><div class="bl-body"><span class="bl-cat"><?php h($r['category']); ?></span><h3><?php h($r['title']); ?></h3></div></a><?php endforeach; ?>
</div></div></section>
<?php endif; ?>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
