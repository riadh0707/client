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
    ['kicker' => 'Nouvelle collection', 'title' => 'Révélez votre <em>éclat</em> naturel', 'text' => 'Maquillage, soins et parfums d\'exception, sélectionnés pour sublimer votre beauté au quotidien.', 'cta' => 'shop.php', 'label' => 'Découvrir la boutique', 'img' => 'banner-01.jpg', 'inset' => 'banner-02.jpg', 'alt' => 'Femme aux longs cheveux ondulés sur fond rose'],
    ['kicker' => 'Soins visage', 'title' => 'La science au service de votre <em>peau</em>', 'text' => 'Des actifs puissants, des formules sensorielles. Offrez à votre peau le rituel qu\'elle mérite.', 'cta' => 'shop.php?cat=soins-visage', 'label' => 'Voir les soins', 'img' => 'banner-05.jpg', 'inset' => 'banner-07.jpg', 'alt' => 'Soin du visage avec masque en institut'],
    ['kicker' => 'Parfums signature', 'title' => 'Votre sillage, votre <em>histoire</em>', 'text' => 'Une collection de parfums raffinés pour marquer les esprits en toute élégance.', 'cta' => 'shop.php?cat=parfums', 'label' => 'Explorer les parfums', 'img' => 'banner-04.jpg', 'inset' => 'banner-08.jpg', 'alt' => 'Femme appliquant un rouge à lèvres'],
];

// Nombre de produits par univers (catégorie parente + sous-catégories).
$catCounts = [];
foreach (Database::all("SELECT COALESCE(c.parent_id, c.id) AS root, COUNT(*) AS n FROM products p JOIN categories c ON c.id = p.category_id WHERE p.deleted_at IS NULL AND p.status = 'active' GROUP BY root") as $row) {
    $catCounts[(int) $row['root']] = (int) $row['n'];
}

$pageTitle = null; // page d'accueil : titre par défaut
require_once INCLUDES_PATH . '/header.php';
?>

<!-- ===================================================== HERO ============ -->
<section class="hero">
  <div class="container">
    <?php foreach ($slides as $i => $s): ?>
    <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= $i ?>"<?= $i > 0 ? ' hidden' : '' ?>>
      <div class="hero-copy">
        <p class="kicker"><?php h($s['kicker']); ?></p>
        <?php if ($i === 0): ?><h1><?= $s['title'] // markup contrôlé ?></h1><?php else: ?><h2 class="h1"><?= $s['title'] ?></h2><?php endif; ?>
        <p><?php h($s['text']); ?></p>
        <div class="hero-cta">
          <a href="<?php h(url($s['cta'])); ?>" class="btn btn-lg"><?php h($s['label']); ?> <?= icon('arrow') ?></a>
          <a href="<?php h(url('shop.php?filter=sale')); ?>" class="btn btn-outline btn-lg">Nos promotions</a>
        </div>
        <div class="hero-proof">
          <span class="faces">
            <?php for ($a = 1; $a <= 4; $a++): ?><img src="<?php h(upload_url(sprintf('avatar-%02d.jpg', $a + $i * 4), 'avatars', 'Cliente')); ?>" alt="" loading="lazy"><?php endfor; ?>
          </span>
          <span><strong>4,9 / 5</strong> · plus de 2 400 clientes conquises</span>
        </div>
      </div>
      <div class="hero-visual">
        <div class="frame"><img src="<?php h(upload_url($s['img'], 'banners', 'Bella Rosa')); ?>" alt="<?php h($s['alt']); ?>"<?= $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>></div>
        <div class="inset"><img src="<?php h(upload_url($s['inset'], 'banners', 'Bella Rosa')); ?>" alt="" loading="lazy"></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="hero-dots" id="heroDots">
      <?php foreach ($slides as $i => $s): ?><button class="<?= $i === 0 ? 'active' : '' ?>" data-dot="<?= $i ?>" aria-label="Afficher : <?php h($s['kicker']); ?>"></button><?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== TRUST =========== -->
<section class="trust">
  <div class="container trust-grid">
    <div class="trust-item"><?= icon('truck') ?><div><strong>Livraison rapide</strong><span>Dans les 58 wilayas</span></div></div>
    <div class="trust-item"><?= icon('wallet') ?><div><strong>Paiement à la livraison</strong><span>Payez à réception</span></div></div>
    <div class="trust-item"><?= icon('shield') ?><div><strong>100 % authentique</strong><span>Produits garantis</span></div></div>
    <div class="trust-item"><?= icon('chat') ?><div><strong>Conseil expert</strong><span>À votre écoute 7j/7</span></div></div>
  </div>
</section>

<!-- ===================================================== CATEGORIES ====== -->
<section class="section">
  <div class="container">
    <?= section_row('Nos univers beauté', 'Trouvez le produit parfait, catégorie par catégorie.', url('shop.php'), 'Toute la boutique') ?>
    <div class="cat-rail">
      <?php foreach ($categories as $c): $n = $catCounts[(int) $c['id']] ?? 0; ?>
      <a href="<?php h(url('shop.php?cat=' . $c['slug'])); ?>" class="cat-arch">
        <span class="ph"><img src="<?php h(upload_url($c['image'] ?? null, 'categories', $c['name'])); ?>" alt="" loading="lazy"></span>
        <strong><?php h($c['name']); ?></strong>
        <span><?= $n ?> produit<?= $n > 1 ? 's' : '' ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== FEATURED ======== -->
<section class="section bg-alt">
  <div class="container">
    <?= section_row('Produits vedettes', 'Nos coups de cœur du moment, plébiscités par nos clientes.', url('shop.php?filter=featured'), 'Voir la sélection') ?>
    <div class="product-grid">
      <?php foreach ($featured as $p) { echo product_card($p); } ?>
    </div>
  </div>
</section>

<!-- ===================================================== EDITORIAL ======= -->
<section class="section">
  <div class="container editorial">
    <div class="ph"><img src="<?php h(upload_url('banner-06.jpg', 'banners', 'Bella Rosa')); ?>" alt="Femme se maquillant devant un miroir" loading="lazy"></div>
    <div>
      <h2>La beauté, choisie avec soin</h2>
      <p>Bella Rosa est née d'une idée simple : rendre accessibles en Algérie les produits de beauté que l'on aime vraiment. Chaque référence est testée, sourcée directement auprès des marques et livrée chez vous avec le même soin qu'un cadeau.</p>
      <ul class="ticks">
        <li><?= icon('check') ?><span><strong>Beauté clean</strong> — des formules respectueuses de la peau et de l'environnement.</span></li>
        <li><?= icon('check') ?><span><strong>Authenticité garantie</strong> — uniquement des produits originaux.</span></li>
        <li><?= icon('check') ?><span><strong>Programme fidélité</strong> — des points à chaque achat, des cadeaux à la clé.</span></li>
        <li><?= icon('check') ?><span><strong>Retour facile</strong> — satisfaite ou remboursée sous 14 jours.</span></li>
      </ul>
      <a href="<?php h(url('about.php')); ?>" class="btn btn-outline btn-lg">Notre histoire <?= icon('arrow') ?></a>
    </div>
  </div>
</section>

<!-- ===================================================== FLASH SALE ====== -->
<section class="section bg-alt">
  <div class="container">
    <div class="flash-sale">
      <div class="fs-copy">
        <span class="badge badge-sale">Offre limitée</span>
        <h2>Vente Flash : jusqu'à -25 %</h2>
        <p>Des réductions exceptionnelles sur une sélection de best-sellers. Les stocks partent vite.</p>
        <div class="countdown" id="countdown" data-deadline="<?= time() + 3600 * 27 ?>">
          <div class="cd-box"><div class="n" data-d>00</div><div class="l">Jours</div></div>
          <div class="cd-box"><div class="n" data-h>00</div><div class="l">Heures</div></div>
          <div class="cd-box"><div class="n" data-m>00</div><div class="l">Min</div></div>
          <div class="cd-box"><div class="n" data-s>00</div><div class="l">Sec</div></div>
        </div>
        <a href="<?php h(url('shop.php?filter=sale')); ?>" class="btn btn-lg">J'en profite <?= icon('arrow') ?></a>
      </div>
      <div class="fs-ph" role="img" aria-label="Femme aux lèvres rouges et lunettes de soleil" style="background-image:url('<?php h(upload_url('banner-08.jpg', 'banners')); ?>')"></div>
    </div>
  </div>
</section>

<!-- ===================================================== NEW ARRIVALS ==== -->
<section class="section">
  <div class="container">
    <?= section_row('Nouveautés', 'Les dernières pépites ajoutées à notre collection.', url('shop.php?filter=new'), 'Toutes les nouveautés') ?>
    <div class="product-grid">
      <?php foreach ($newArrivals as $p) { echo product_card($p); } ?>
    </div>
  </div>
</section>

<!-- ===================================================== BESTSELLERS ===== -->
<section class="section bg-alt">
  <div class="container">
    <?= section_row('Meilleures ventes', 'Les produits que nos clientes recommandent le plus.', url('shop.php?filter=bestseller'), 'Voir le classement') ?>
    <div class="product-grid">
      <?php foreach (array_merge($bestsellers, $popular) as $p) { echo product_card($p); } ?>
    </div>
  </div>
</section>

<!-- ===================================================== BRANDS ========== -->
<section class="section">
  <div class="container">
    <?= section_head('', 'Nos marques partenaires') ?>
    <div class="brand-strip">
      <?php foreach ($brands as $b): ?>
      <a href="<?php h(url('shop.php?brand=' . $b['slug'])); ?>" class="brand-chip"><?php h($b['name']); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== TESTIMONIALS ==== -->
<section class="section bg-alt">
  <div class="container">
    <?= section_head('', 'Elles nous font confiance') ?>
    <div class="testi-grid">
      <?php foreach ($testimonials as $t): ?>
      <figure class="testi-card">
        <?= star_rating((float) $t['rating']) ?>
        <blockquote><p><?php h($t['body']); ?></p></blockquote>
        <figcaption class="testi-who">
          <img src="<?php h(upload_url($t['avatar'] ?? null, 'avatars', $t['name'])); ?>" alt="" loading="lazy">
          <div><strong><?php h($t['name']); ?></strong><span><?php h($t['role']); ?></span></div>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================================================== BLOG ============ -->
<section class="section">
  <div class="container">
    <?= section_row('Conseils & tutoriels', 'Le journal beauté de Bella Rosa.', url('blog.php'), 'Tous les articles') ?>
    <div class="blog-grid">
      <?php foreach ($posts as $post): ?>
      <a href="<?php h(url('blog-post.php?slug=' . $post['slug'])); ?>" class="blog-card">
        <div class="bc-media"><img src="<?php h(upload_url($post['image'] ?? null, 'blog', $post['title'])); ?>" alt="" loading="lazy"></div>
        <div class="bc-body">
          <span class="bc-cat"><?php h($post['category']); ?></span>
          <h3><?php h($post['title']); ?></h3>
          <p><?php h(excerpt($post['excerpt'], 110)); ?></p>
          <div class="bc-meta"><span><?= icon('user') ?> <?php h($post['author']); ?></span><span><?= icon('clock') ?> <?php h(fdate($post['published_at'])); ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
