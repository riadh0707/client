<?php
/** header.php - En-tête HTML commun (partie publique). */
require_once __DIR__ . '/icons.php';

$meta  = meta_defaults();
$title = isset($pageTitle) ? $pageTitle . ' · ' . setting('site_name', 'La Bibliothèque Numérique') : $meta['title'];
$desc  = $pageDescription ?? $meta['description'];
$ogImg = $ogImage ?? $meta['image'];

try { $navCats = Database::all('SELECT name, slug FROM categories WHERE deleted_at IS NULL ORDER BY position'); }
catch (Throwable $e) { $navCats = []; }
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php h($title); ?></title>
<meta name="description" content="<?php h($desc); ?>">
<meta name="theme-color" content="#4a3220">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php h($title); ?>">
<meta property="og:description" content="<?php h($desc); ?>">
<meta property="og:image" content="<?php h($ogImg); ?>">
<meta property="og:site_name" content="<?php h(setting('site_name', 'La Bibliothèque Numérique')); ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?php h(asset('images/favicon.svg')); ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Poppins:wght@300;400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php h(asset('css/style.css')); ?>">
<script>
  document.documentElement.classList.add('js');
  (function(){ try { var t = localStorage.getItem('theme'); if (t) document.documentElement.setAttribute('data-theme', t); } catch(e){} })();
  window.BIBLIO = { base: '<?php h(BASE_URL); ?>', csrf: '<?php h(csrf_token()); ?>', currency: '<?php h($GLOBALS['config']['app']['currency_symbol'] ?? 'DA'); ?>' };
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BookStore","name":"<?php h(setting('site_name','La Bibliothèque Numérique')); ?>","description":"<?php h($desc); ?>","telephone":"<?php h(setting('contact_phone')); ?>","address":{"@type":"PostalAddress","addressLocality":"Alger","addressCountry":"DZ"}}
</script>
</head>
<body class="<?php h($bodyClass ?? ''); ?>">

<?php if ($ann = setting('announcement')): ?>
<div class="topbar">✦ <?php h($ann); ?></div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
  <div class="container header-inner">
    <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Menu"><?= icon('menu') ?></button>

    <a href="<?php h(url('index.php')); ?>" class="logo">
      <span class="mark"><?= icon('library') ?></span>
      <span class="txt"><b>La Bibliothèque</b><small>المكتبة الرقمية</small></span>
    </a>

    <ul class="main-nav">
      <li><a href="<?php h(url('index.php')); ?>">Accueil</a></li>
      <li><a href="<?php h(url('catalogue.php')); ?>">Catalogue</a></li>
      <li><a href="<?php h(url('catalogue.php?filter=new')); ?>">Nouveautés</a></li>
      <li><a href="<?php h(url('catalogue.php?filter=sale')); ?>">Promotions</a></li>
      <li><a href="<?php h(url('blog.php')); ?>">Le carnet</a></li>
      <li><a href="<?php h(url('contact.php')); ?>">Contact</a></li>
    </ul>

    <div class="header-actions">
      <button class="icon-btn" id="searchBtn" aria-label="Rechercher"><?= icon('search') ?></button>
      <button class="icon-btn header-extra" id="themeBtn" aria-label="Thème"><?= icon('moon') ?></button>
      <a href="<?php h(url('wishlist.php')); ?>" class="icon-btn header-extra" aria-label="Ma liste">
        <?= icon('bookmark') ?>
        <span class="count" id="wishCount" style="<?= empty($_SESSION['wishlist']) ? 'display:none' : '' ?>"><?= count($_SESSION['wishlist'] ?? []) ?></span>
      </a>
      <a href="<?php h(url(is_logged_in() ? 'customer/library.php' : 'customer/login.php')); ?>" class="icon-btn" aria-label="Mon compte"><?= icon('user') ?></a>
      <a href="<?php h(url('cart.php')); ?>" class="icon-btn" aria-label="Panier">
        <?= icon('cart') ?>
        <span class="count" id="cartCount" style="<?= cart_count() === 0 ? 'display:none' : '' ?>"><?= cart_count() ?></span>
      </a>
    </div>
  </div>
</header>

<div class="search-overlay" id="searchOverlay">
  <div class="search-panel">
    <div class="search-box">
      <?= icon('search') ?>
      <input type="search" id="searchInput" placeholder="Rechercher un livre, une présentation, un thème…" autocomplete="off">
      <button class="icon-btn" id="searchClose" aria-label="Fermer"><?= icon('close') ?></button>
    </div>
    <div class="search-results" id="searchResults"><p class="search-hint">Commencez à taper pour voir des suggestions…</p></div>
  </div>
</div>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<nav class="mobile-drawer" id="mobileDrawer">
  <a href="<?php h(url('index.php')); ?>" class="logo" style="margin-bottom:20px"><span class="txt"><b>La Bibliothèque</b><small>المكتبة الرقمية</small></span></a>
  <div class="mobile-nav">
    <a href="<?php h(url('index.php')); ?>">Accueil</a>
    <a href="<?php h(url('catalogue.php')); ?>">Catalogue</a>
    <?php foreach ($navCats as $c): ?><a href="<?php h(url('catalogue.php?cat=' . $c['slug'])); ?>"><?php h($c['name']); ?></a><?php endforeach; ?>
    <a href="<?php h(url('catalogue.php?filter=new')); ?>">Nouveautés</a>
    <a href="<?php h(url('catalogue.php?filter=sale')); ?>">Promotions</a>
    <a href="<?php h(url('blog.php')); ?>">Le carnet</a>
    <a href="<?php h(url('about.php')); ?>">À propos</a>
    <a href="<?php h(url('contact.php')); ?>">Contact</a>
    <a href="<?php h(url('wishlist.php')); ?>">Ma liste de lecture</a>
    <a href="<?php h(url(is_logged_in() ? 'customer/library.php' : 'customer/login.php')); ?>">Ma bibliothèque</a>
    <a href="#" id="drawerTheme">Mode sombre / clair</a>
  </div>
</nav>

<?php foreach (get_flashes() as $f): ?>
<script>window.__flash = window.__flash || []; window.__flash.push(<?= json_encode($f, JSON_UNESCAPED_UNICODE) ?>);</script>
<?php endforeach; ?>

<main id="main">
