<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$featured    = books_by_flag('featured', 8);
$newArrivals = books_by_flag('new', 4);
$bestsellers = books_by_flag('bestseller', 4);
$onSale      = books_by_flag('sale', 4);
$categories  = all_categories();
$testimonials = Database::all('SELECT * FROM testimonials ORDER BY position LIMIT 6');
$posts       = Database::all("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 3");
$heroBook    = $featured[0] ?? ($bestsellers[0] ?? null);

require_once INCLUDES_PATH . '/header.php';
?>

<!-- ===================================================== HERO ============ -->
<section class="hero">
  <span class="corner-orn tl"><?= icon('sparkles') ?></span>
  <span class="corner-orn tr"><?= icon('sparkles') ?></span>
  <div class="container">
    <div class="hero-inner">
      <div class="hero-copy">
        <span class="eyebrow">La bibliothèque numérique</span>
        <h1>Des livres et des présentations à <em>lire</em> en ligne</h1>
        <p>Ouvrages PDF et présentations PowerPoint — feuilletez un aperçu gratuit, puis lisez le titre entier en ligne après votre achat.</p>
        <div class="hero-cta">
          <a href="<?php h(url('catalogue.php')); ?>" class="btn btn-lg"><?= icon('library') ?> Découvrir les livres</a>
          <?php if ($heroBook): ?><a href="<?php h(url('lire.php?slug=' . $heroBook['slug'])); ?>" class="btn btn-outline btn-lg"><?= icon('book-open') ?> Lire un aperçu</a><?php endif; ?>
        </div>
      </div>
      <?php if ($heroBook): ?>
      <div class="book-3d">
        <div class="book">
          <span class="spine"></span><span class="pages"></span>
          <a class="cover" href="<?php h(url('livre.php?slug=' . $heroBook['slug'])); ?>"><img src="<?php h(upload_url($heroBook['cover_image'] ?? null, 'covers', $heroBook['title'])); ?>" alt="<?php h($heroBook['title']); ?>"></a>
        </div>
        <div class="hero-float" style="top:8%;left:2%"><span class="fi"><?= icon('book-open') ?></span><div><small>Aperçu</small><strong>Gratuit</strong></div></div>
        <div class="hero-float" style="bottom:10%;right:2%;animation-delay:1.2s"><span class="fi"><?= icon('star') ?></span><div><small>Lectrices</small><strong>4,9 / 5</strong></div></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===================================================== TRUST =========== -->
<section class="trust">
  <div class="container trust-grid">
    <div class="trust-item"><?= icon('book-open') ?><div><strong>Aperçu gratuit</strong><span>Avant chaque achat</span></div></div>
    <div class="trust-item"><?= icon('wallet') ?><div><strong>Paiement BaridiMob</strong><span>Simple et sécurisé</span></div></div>
    <div class="trust-item"><?= icon('unlock') ?><div><strong>Lecture immédiate</strong><span>En ligne après validation</span></div></div>
    <div class="trust-item"><?= icon('presentation') ?><div><strong>PDF &amp; PowerPoint</strong><span>Lecture dans le navigateur</span></div></div>
  </div>
</section>

<!-- ===================================================== CATEGORIES ====== -->
<section class="section">
  <div class="container">
    <?= section_head('Explorez', 'Nos rayons', 'Trouvez le livre qui vous inspire, thème par thème.') ?>
    <div class="cat-grid">
      <?php foreach (array_slice($categories, 0, 6) as $c): ?>
      <a href="<?php h(url('catalogue.php?cat=' . $c['slug'])); ?>" class="cat-card reveal">
        <img src="<?php h(upload_url($c['image'] ?? null, 'categories', $c['name'])); ?>" alt="<?php h($c['name']); ?>" loading="lazy">
        <div class="cat-label"><h3><?php h($c['name']); ?></h3><span>Découvrir →</span></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== FEATURED ======== -->
<section class="section" style="background:var(--paper-3)">
  <div class="container">
    <?= section_head('À la une', 'Les livres vedettes', 'Nos coups de cœur, plébiscités par nos lecteurs.') ?>
    <div class="book-grid">
      <?php foreach (array_slice($featured, 0, 4) as $b) { echo book_card($b); } ?>
    </div>
    <div class="center" style="margin-top:40px"><a href="<?php h(url('catalogue.php')); ?>" class="btn btn-outline btn-lg">Voir tout le catalogue <?= icon('arrow') ?></a></div>
  </div>
</section>

<!-- ===================================================== FEATURED BOOK ==== -->
<?php if ($heroBook): ?>
<section class="section">
  <div class="container">
    <div class="feature-book reveal">
      <span class="corner-orn tl"><?= icon('sparkles') ?></span>
      <div class="fb-cover"><img src="<?php h(upload_url($heroBook['cover_image'] ?? null, 'covers', $heroBook['title'])); ?>" alt="<?php h($heroBook['title']); ?>"></div>
      <div>
        <span class="eyebrow">Le livre du moment</span>
        <h2><?php h($heroBook['title']); ?></h2>
        <div class="bc-rating" style="margin-bottom:12px"><?= star_rating((float) $heroBook['rating'], true) ?> <span>· <?= (int) $heroBook['pages_count'] ?> pages</span></div>
        <p style="color:var(--muted);font-family:var(--font-serif);font-size:1.2rem"><?php h($heroBook['long_desc']); ?></p>
        <div class="hero-cta" style="margin-top:20px">
          <a href="<?php h(url('livre.php?slug=' . $heroBook['slug'])); ?>" class="btn btn-lg"><?= e(book_price_label($heroBook)) ?> — Voir le livre</a>
          <a href="<?php h(url('lire.php?slug=' . $heroBook['slug'])); ?>" class="btn btn-ghost btn-lg"><?= icon('book-open') ?> Aperçu gratuit</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================================================== PROMO =========== -->
<?php if ($onSale): ?>
<section class="section" style="background:var(--paper-3)">
  <div class="container">
    <div class="flash reveal">
      <div style="position:relative;z-index:2;max-width:560px">
        <span class="eyebrow">Offre limitée</span>
        <h2>Promotions du moment</h2>
        <p>Profitez de prix réduits sur une sélection de livres. Aperçu gratuit toujours disponible !</p>
        <div class="countdown" id="countdown" data-deadline="<?= time() + 3600 * 30 ?>">
          <div class="cd-box"><div class="n" data-d>00</div><div class="l">Jours</div></div>
          <div class="cd-box"><div class="n" data-h>00</div><div class="l">Heures</div></div>
          <div class="cd-box"><div class="n" data-m>00</div><div class="l">Min</div></div>
          <div class="cd-box"><div class="n" data-s>00</div><div class="l">Sec</div></div>
        </div>
        <a href="<?php h(url('catalogue.php?filter=sale')); ?>" class="btn btn-gold btn-lg">Voir les promotions <?= icon('arrow') ?></a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================================================== NEW + BEST ====== -->
<section class="section">
  <div class="container">
    <?= section_head('Fraîchement publiés', 'Nouveautés & best-sellers') ?>
    <div class="book-grid">
      <?php foreach (array_merge($newArrivals, $bestsellers) as $b) { echo book_card($b); } ?>
    </div>
  </div>
</section>

<!-- ===================================================== HOW IT WORKS ==== -->
<section class="section" style="background:var(--paper-3)">
  <div class="container">
    <?= section_head('En trois étapes', 'Comment ça marche') ?>
    <div class="trust-grid" style="gap:24px">
      <div class="stat-card reveal center"><div style="color:var(--coffee);margin-bottom:10px"><?= icon('book-open') ?></div><strong>1. Feuilletez</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Lisez gratuitement les premières pages ou diapositives de chaque titre.</p></div>
      <div class="stat-card reveal center"><div style="color:var(--coffee);margin-bottom:10px"><?= icon('cart') ?></div><strong>2. Commandez</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Ajoutez au panier et payez par BaridiMob.</p></div>
      <div class="stat-card reveal center"><div style="color:var(--coffee);margin-bottom:10px"><?= icon('check') ?></div><strong>3. Validation</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Notre équipe confirme votre paiement.</p></div>
      <div class="stat-card reveal center"><div style="color:var(--coffee);margin-bottom:10px"><?= icon('unlock') ?></div><strong>4. Lisez tout</strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0">Le livre entier s'ouvre dans votre bibliothèque.</p></div>
    </div>
  </div>
</section>

<!-- ===================================================== TESTIMONIALS ==== -->
<section class="section">
  <div class="container">
    <?= section_head('Ils nous lisent', 'Témoignages de nos lecteurs') ?>
    <div class="testi-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card reveal">
        <div class="quote">“</div><?= star_rating((float) $t['rating']) ?>
        <p><?php h($t['body']); ?></p>
        <div class="testi-who"><span class="av"><?php h(initials($t['name'])); ?></span><div><strong><?php h($t['name']); ?></strong><span><?php h($t['role']); ?></span></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== BLOG ============ -->
<section class="section" style="background:var(--paper-3)">
  <div class="container">
    <?= section_head('Le carnet', 'Conseils & actualités de notre équipe') ?>
    <div class="blog-grid">
      <?php foreach ($posts as $post): ?>
      <a href="<?php h(url('blog-post.php?slug=' . $post['slug'])); ?>" class="blog-card reveal">
        <div class="bl-media"><img src="<?php h(upload_url($post['image'] ?? null, 'blog', $post['title'])); ?>" alt="<?php h($post['title']); ?>" loading="lazy"></div>
        <div class="bl-body">
          <span class="bl-cat"><?php h($post['category']); ?></span>
          <h3><?php h($post['title']); ?></h3>
          <p style="color:var(--muted);font-size:.9rem"><?php h(excerpt($post['excerpt'], 90)); ?></p>
          <div class="bl-meta"><span><?= icon('user') ?> <?php h($post['author']); ?></span><span><?= icon('clock') ?> <?php h(fdate($post['published_at'])); ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
