<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$featured    = products_by_flag('featured', 8);
$newArrivals = products_by_flag('new', 8);
$bestsellers = products_by_flag('bestseller', 4);
$onSale      = products_by_flag('sale', 4);
$popular     = products_popular(4);
$categories  = array_filter(all_categories(), fn($c) => $c['parent_id'] === null);
$brands      = all_brands();
$testimonials = Database::all('SELECT * FROM testimonials ORDER BY position LIMIT 6');
$posts       = Database::all("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 3");

$slides = [
    ['eyebrow' => 'Nouvelle collection', 'title' => 'Révélez votre <em>éclat</em> naturel', 'text' => 'Maquillage, soins et parfums d\'exception, sélectionnés pour sublimer votre beauté au quotidien.', 'cta' => 'shop.php', 'label' => 'Découvrir la boutique'],
    ['eyebrow' => 'Soins visage premium', 'title' => 'La science au service de votre <em>peau</em>', 'text' => 'Des actifs puissants, des formules sensorielles. Offrez à votre peau le rituel qu\'elle mérite.', 'cta' => 'shop.php?cat=soins-visage', 'label' => 'Voir les soins'],
    ['eyebrow' => 'Parfums signature', 'title' => 'Votre sillage, votre <em>histoire</em>', 'text' => 'Une collection de parfums raffinés pour marquer les esprits en toute élégance.', 'cta' => 'shop.php?cat=parfums', 'label' => 'Explorer les parfums'],
];

$pageTitle = null; // page d'accueil : titre par défaut
require_once INCLUDES_PATH . '/header.php';
?>

<!-- ===================================================== HERO ============ -->
<section class="hero">
  <span class="blob b1"></span><span class="blob b2"></span>
  <div class="container" style="position:relative;z-index:2">
    <?php foreach ($slides as $i => $s): ?>
    <div class="hero-slide" data-slide="<?= $i ?>" style="<?= $i > 0 ? 'display:none' : '' ?>">
      <div class="hero-copy">
        <span class="eyebrow"><?php h($s['eyebrow']); ?></span>
        <h1><?= $s['title'] // markup contrôlé ?></h1>
        <p><?php h($s['text']); ?></p>
        <div class="hero-cta">
          <a href="<?php h(url($s['cta'])); ?>" class="btn btn-lg"><?php h($s['label']); ?> <?= icon('arrow') ?></a>
          <a href="<?php h(url('shop.php?filter=sale')); ?>" class="btn btn-outline btn-lg">Nos promotions</a>
        </div>
      </div>
      <div class="hero-visual">
        <div class="frame"><img src="<?php h(upload_url(null, 'banners', ['Éclat', 'Soins', 'Parfums'][$i])); ?>" alt="Bella Rosa"></div>
        <div class="hero-float f1"><span class="fi"><?= icon('truck') ?></span><div><small>Livraison</small><strong>58 wilayas</strong></div></div>
        <div class="hero-float f2"><span class="fi"><?= icon('star') ?></span><div><small>+2 400 avis</small><strong>4,9 / 5</strong></div></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="hero-dots" id="heroDots">
      <?php foreach ($slides as $i => $s): ?><button class="<?= $i === 0 ? 'active' : '' ?>" data-dot="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== TRUST =========== -->
<section class="trust">
  <div class="container trust-grid">
    <div class="trust-item"><?= icon('truck') ?><div><strong>Livraison rapide</strong><span>Partout en Algérie</span></div></div>
    <div class="trust-item"><?= icon('wallet') ?><div><strong>Paiement à la livraison</strong><span>Payez à réception</span></div></div>
    <div class="trust-item"><?= icon('shield') ?><div><strong>100% authentique</strong><span>Produits garantis</span></div></div>
    <div class="trust-item"><?= icon('chat') ?><div><strong>Conseil expert</strong><span>À votre écoute 7j/7</span></div></div>
  </div>
</section>

<!-- ===================================================== CATEGORIES ====== -->
<section class="section">
  <div class="container">
    <?= section_head('Explorez', 'Nos univers beauté', 'Trouvez le produit parfait, catégorie par catégorie.') ?>
    <div class="cat-grid">
      <?php foreach (array_slice($categories, 0, 4) as $c): ?>
      <a href="<?php h(url('shop.php?cat=' . $c['slug'])); ?>" class="cat-card reveal">
        <img src="<?php h(upload_url($c['image'] ?? null, 'categories', $c['name'])); ?>" alt="<?php h($c['name']); ?>" loading="lazy">
        <div class="cat-label"><h3><?php h($c['name']); ?></h3><span>Découvrir →</span></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if (count($categories) > 4): ?>
    <div class="cat-grid" style="margin-top:20px">
      <?php foreach (array_slice($categories, 4, 3) as $c): ?>
      <a href="<?php h(url('shop.php?cat=' . $c['slug'])); ?>" class="cat-card reveal" style="aspect-ratio:16/10">
        <img src="<?php h(upload_url($c['image'] ?? null, 'categories', $c['name'])); ?>" alt="<?php h($c['name']); ?>" loading="lazy">
        <div class="cat-label"><h3><?php h($c['name']); ?></h3><span>Découvrir →</span></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ===================================================== FEATURED ======== -->
<section class="section" style="background:var(--bg-alt)">
  <div class="container">
    <?= section_head('Sélection', 'Produits vedettes', 'Nos coups de cœur du moment, plébiscités par nos clientes.') ?>
    <div class="product-grid">
      <?php foreach ($featured as $p) { echo product_card($p); } ?>
    </div>
    <div class="center" style="margin-top:40px"><a href="<?php h(url('shop.php')); ?>" class="btn btn-outline btn-lg">Voir toute la boutique <?= icon('arrow') ?></a></div>
  </div>
</section>

<!-- ===================================================== FLASH SALE ====== -->
<section class="section">
  <div class="container">
    <div class="flash-sale reveal">
      <span class="blob b1" style="opacity:.25"></span>
      <div style="position:relative;z-index:2;max-width:560px">
        <span class="eyebrow">Offre limitée</span>
        <h2>Vente Flash — jusqu'à -25%</h2>
        <p>Profitez de réductions exceptionnelles sur une sélection de best-sellers. Dépêchez-vous, les stocks sont limités !</p>
        <div class="countdown" id="countdown" data-deadline="<?= time() + 3600 * 27 ?>">
          <div class="cd-box"><div class="n" data-d>00</div><div class="l">Jours</div></div>
          <div class="cd-box"><div class="n" data-h>00</div><div class="l">Heures</div></div>
          <div class="cd-box"><div class="n" data-m>00</div><div class="l">Min</div></div>
          <div class="cd-box"><div class="n" data-s>00</div><div class="l">Sec</div></div>
        </div>
        <a href="<?php h(url('shop.php?filter=sale')); ?>" class="btn btn-light btn-lg">J'en profite <?= icon('arrow') ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ===================================================== NEW ARRIVALS ==== -->
<section class="section">
  <div class="container">
    <?= section_head('Fraîchement arrivés', 'Nouveautés', 'Les dernières pépites ajoutées à notre collection.') ?>
    <div class="product-grid">
      <?php foreach ($newArrivals as $p) { echo product_card($p); } ?>
    </div>
  </div>
</section>

<!-- ===================================================== BESTSELLERS ===== -->
<section class="section" style="background:var(--bg-alt)">
  <div class="container">
    <?= section_head('Les préférés', 'Meilleures ventes') ?>
    <div class="product-grid">
      <?php foreach (array_merge($bestsellers, $popular) as $p) { echo product_card($p); } ?>
    </div>
  </div>
</section>

<!-- ===================================================== BRANDS ========== -->
<section class="section">
  <div class="container">
    <?= section_head('Ils nous font confiance', 'Nos marques partenaires') ?>
    <div class="brand-strip">
      <?php foreach ($brands as $b): ?>
      <a href="<?php h(url('shop.php?brand=' . $b['slug'])); ?>" class="brand-chip reveal"><span class="dot"><?php h(initials($b['name'])); ?></span> <?php h($b['name']); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== WHY US ========== -->
<section class="section" style="background:var(--bg-alt)">
  <div class="container">
    <?= section_head('Pourquoi Bella Rosa', 'Nos engagements') ?>
    <div class="trust-grid" style="gap:26px">
      <div class="stat-card reveal"><div style="color:var(--rose);margin-bottom:10px"><?= icon('leaf') ?></div><strong>Beauté clean</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Des formules respectueuses de votre peau et de l'environnement.</p></div>
      <div class="stat-card reveal"><div style="color:var(--rose);margin-bottom:10px"><?= icon('shield') ?></div><strong>Authenticité garantie</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Uniquement des produits originaux, directement des marques.</p></div>
      <div class="stat-card reveal"><div style="color:var(--rose);margin-bottom:10px"><?= icon('gift') ?></div><strong>Programme fidélité</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Cumulez des points à chaque achat et débloquez des récompenses.</p></div>
      <div class="stat-card reveal"><div style="color:var(--rose);margin-bottom:10px"><?= icon('refresh') ?></div><strong>Retour facile</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Satisfait ou remboursé sous 14 jours, sans complication.</p></div>
    </div>
  </div>
</section>

<!-- ===================================================== TESTIMONIALS ==== -->
<section class="section">
  <div class="container">
    <?= section_head('Elles nous adorent', 'Témoignages clientes') ?>
    <div class="testi-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card reveal">
        <div class="quote">"</div>
        <?= star_rating((float) $t['rating']) ?>
        <p><?php h($t['body']); ?></p>
        <div class="testi-who">
          <img src="<?php h(upload_url($t['avatar'] ?? null, 'avatars', $t['name'])); ?>" alt="<?php h($t['name']); ?>">
          <div><strong><?php h($t['name']); ?></strong><span><?php h($t['role']); ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== BLOG ============ -->
<section class="section" style="background:var(--bg-alt)">
  <div class="container">
    <?= section_head('Journal beauté', 'Conseils & tutoriels') ?>
    <div class="blog-grid">
      <?php foreach ($posts as $post): ?>
      <a href="<?php h(url('blog-post.php?slug=' . $post['slug'])); ?>" class="blog-card reveal">
        <div class="bc-media"><img src="<?php h(upload_url($post['image'] ?? null, 'blog', $post['title'])); ?>" alt="<?php h($post['title']); ?>" loading="lazy"></div>
        <div class="bc-body">
          <span class="bc-cat"><?php h($post['category']); ?></span>
          <h3><?php h($post['title']); ?></h3>
          <p style="color:var(--muted);font-size:.9rem"><?php h(excerpt($post['excerpt'], 90)); ?></p>
          <div class="bc-meta"><span><?= icon('user') ?> <?php h($post['author']); ?></span><span><?= icon('clock') ?> <?php h(fdate($post['published_at'])); ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
