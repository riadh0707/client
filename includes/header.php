<?php
/**
 * header.php — En-tête HTML commun à la partie publique.
 * Les pages définissent (facultatif) : $pageTitle, $pageDescription, $ogImage, $bodyClass.
 */
require_once __DIR__ . '/icons.php';

$meta  = meta_defaults();
$title = isset($pageTitle) ? $pageTitle . ' — ' . setting('site_name', 'Bella Rosa') : $meta['title'];
$desc  = $pageDescription ?? $meta['description'];
$ogImg = $ogImage ?? $meta['image'];

// Catégories pour la navigation (parents + enfants).
try {
    $navCats = Database::all('SELECT id, parent_id, name, slug, icon FROM categories WHERE deleted_at IS NULL ORDER BY position');
} catch (Throwable $e) {
    $navCats = [];
}
$navParents = array_filter($navCats, fn($c) => $c['parent_id'] === null);
$navChildren = [];
foreach ($navCats as $c) {
    if ($c['parent_id'] !== null) { $navChildren[$c['parent_id']][] = $c; }
}
?><!doctype html>
<html lang="<?php h($GLOBALS['config']['app']['locale'] ?? 'fr'); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php h($title); ?></title>
<meta name="description" content="<?php h($desc); ?>">
<meta name="theme-color" content="#b76e79">
<!-- Open Graph / SEO -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?php h($title); ?>">
<meta property="og:description" content="<?php h($desc); ?>">
<meta property="og:image" content="<?php h($ogImg); ?>">
<meta property="og:site_name" content="<?php h(setting('site_name', 'Bella Rosa')); ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="<?php h((isset($_SERVER['HTTPS'])?'https':'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '')); ?>">
<link rel="icon" href="<?php h(asset('images/favicon.svg')); ?>" type="image/svg+xml">
<!-- Polices -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php h(asset('css/style.css')); ?>">
<script>
  // Application immédiate du thème (évite le flash).
  document.documentElement.classList.add('js');
  (function(){ try { var t = localStorage.getItem('theme'); if (t) document.documentElement.setAttribute('data-theme', t); } catch(e){} })();
  window.BELLA = { base: '<?php h(BASE_URL); ?>', csrf: '<?php h(csrf_token()); ?>', currency: '<?php h($GLOBALS['config']['app']['currency_symbol'] ?? 'DA'); ?>' };
</script>
<!-- Schema.org -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Store","name":"<?php h(setting('site_name','Bella Rosa')); ?>","description":"<?php h($desc); ?>","telephone":"<?php h(setting('contact_phone')); ?>","address":{"@type":"PostalAddress","addressLocality":"Alger","addressCountry":"DZ"}}
</script>
</head>
<body class="<?php h($bodyClass ?? ''); ?>">

<!-- Bandeau annonce -->
<?php if ($ann = setting('announcement')): ?>
<div class="topbar"><div class="track"><span>✦ <?php h($ann); ?></span><span>✦ <?php h($ann); ?></span></div></div>
<?php endif; ?>

<!-- En-tête -->
<header class="site-header" id="siteHeader">
  <div class="container header-inner">
    <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Menu"><?= icon('menu') ?></button>

    <a href="<?php h(url('index.php')); ?>" class="logo">
      <span class="mark">✿</span> Bella<b>Rosa</b>
    </a>

    <ul class="main-nav">
      <li><a href="<?php h(url('index.php')); ?>">Accueil</a></li>
      <li class="has-mega">
        <a href="<?php h(url('shop.php')); ?>">Boutique <?= icon('chevron') ?></a>
        <div class="mega"><div class="mega-grid">
          <?php foreach (array_slice($navParents, 0, 4) as $navP): ?>
          <div class="mega-col">
            <h4><a href="<?php h(url('shop.php?cat=' . $navP['slug'])); ?>"><?php h($navP['name']); ?></a></h4>
            <?php foreach (($navChildren[$navP['id']] ?? []) as $ch): ?>
              <a href="<?php h(url('shop.php?cat=' . $ch['slug'])); ?>"><?php h($ch['name']); ?></a>
            <?php endforeach; ?>
            <a href="<?php h(url('shop.php?cat=' . $navP['slug'])); ?>" class="text-rose">Tout voir →</a>
          </div>
          <?php endforeach; ?>
          <div class="mega-promo">
            <span class="badge badge-sale">-25%</span>
            <strong>Vente Flash</strong>
            <span>Jusqu'à -25% sur une sélection beauté</span>
          </div>
        </div></div>
      </li>
      <li><a href="<?php h(url('shop.php?filter=new')); ?>">Nouveautés</a></li>
      <li><a href="<?php h(url('shop.php?filter=sale')); ?>">Promotions</a></li>
      <li><a href="<?php h(url('blog.php')); ?>">Blog</a></li>
      <li><a href="<?php h(url('contact.php')); ?>">Contact</a></li>
    </ul>

    <div class="header-actions">
      <button class="icon-btn" id="searchBtn" aria-label="Rechercher"><?= icon('search') ?></button>
      <button class="icon-btn header-extra" id="themeBtn" aria-label="Mode sombre"><?= icon('moon') ?></button>
      <a href="<?php h(url('wishlist.php')); ?>" class="icon-btn header-extra" aria-label="Favoris">
        <?= icon('heart') ?>
        <span class="count" id="wishCount" style="<?= empty($_SESSION['wishlist']) ? 'display:none' : '' ?>"><?= count($_SESSION['wishlist'] ?? []) ?></span>
      </a>
      <a href="<?php h(url(is_logged_in() ? 'customer/account.php' : 'customer/login.php')); ?>" class="icon-btn" aria-label="Compte"><?= icon('user') ?></a>
      <a href="<?php h(url('cart.php')); ?>" class="icon-btn" aria-label="Panier">
        <?= icon('cart') ?>
        <span class="count" id="cartCount" style="<?= cart_count() === 0 ? 'display:none' : '' ?>"><?= cart_count() ?></span>
      </a>
    </div>
  </div>
</header>

<!-- Recherche -->
<div class="search-overlay" id="searchOverlay">
  <div class="search-panel">
    <div class="search-box">
      <?= icon('search') ?>
      <input type="search" id="searchInput" placeholder="Rechercher un produit, une marque…" autocomplete="off">
      <button class="icon-btn" id="searchClose" aria-label="Fermer"><?= icon('close') ?></button>
    </div>
    <div class="search-results" id="searchResults">
      <p class="search-hint">Commencez à taper pour voir des suggestions…</p>
    </div>
  </div>
</div>

<!-- Menu mobile -->
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<nav class="mobile-drawer" id="mobileDrawer">
  <a href="<?php h(url('index.php')); ?>" class="logo" style="margin-bottom:20px">Bella<b>Rosa</b></a>
  <div class="mobile-nav">
    <a href="<?php h(url('index.php')); ?>">Accueil</a>
    <a href="<?php h(url('shop.php')); ?>">Boutique</a>
    <?php foreach ($navParents as $navP): ?>
      <a href="<?php h(url('shop.php?cat=' . $navP['slug'])); ?>">— <?php h($navP['name']); ?></a>
    <?php endforeach; ?>
    <a href="<?php h(url('shop.php?filter=new')); ?>">Nouveautés</a>
    <a href="<?php h(url('shop.php?filter=sale')); ?>">Promotions</a>
    <a href="<?php h(url('blog.php')); ?>">Blog</a>
    <a href="<?php h(url('about.php')); ?>">À propos</a>
    <a href="<?php h(url('contact.php')); ?>">Contact</a>
    <a href="<?php h(url('wishlist.php')); ?>">Mes favoris</a>
    <a href="<?php h(url(is_logged_in() ? 'customer/account.php' : 'customer/login.php')); ?>">Mon compte</a>
    <a href="#" id="drawerTheme">Mode sombre / clair</a>
  </div>
</nav>

<?php foreach (get_flashes() as $f): ?>
<script>window.__flash = window.__flash || []; window.__flash.push(<?= json_encode($f, JSON_UNESCAPED_UNICODE) ?>);</script>
<?php endforeach; ?>

<main id="main">
