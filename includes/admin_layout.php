<?php
/** admin_layout.php — En-tête + sidebar admin. Définir $adminTitle, $adminActive. */
require_once __DIR__ . '/icons.php';
$admin = current_admin();
$adminActive = $adminActive ?? '';
$adminTitle = $adminTitle ?? 'Tableau de bord';
$nav = [
    'main' => [
        'dashboard' => ['index.php', 'Tableau de bord', 'grid'],
        'orders'    => ['orders.php', 'Commandes', 'receipt'],
        'books'     => ['books.php', 'Livres & présentations', 'book'],
        'categories'=> ['categories.php', 'Rayons', 'library'],
        'customers' => ['customers.php', 'Clients', 'user'],
    ],
    'content' => [
        'reviews'    => ['reviews.php', 'Avis', 'star'],
        'blog'       => ['blog.php', 'Le carnet', 'feather'],
        'promotions' => ['promotions.php', 'Promotions', 'tag'],
        'settings'   => ['settings.php', 'Paramètres', 'sun'],
    ],
];
?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php h($adminTitle); ?> — Admin</title>
<link rel="icon" href="<?php h(asset('images/favicon.svg')); ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php h(asset('css/admin.css')); ?>">
<script>(function(){try{var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
</head><body class="admin">
<div class="admin-shell">
  <aside class="a-sidebar" id="aSidebar">
    <div class="a-logo"><span class="mark"><?= icon('library') ?></span> La Bibliothèque</div>
    <div class="a-nav-label">Gestion</div>
    <nav class="a-nav"><?php foreach ($nav['main'] as $k => $l): ?><a href="<?php h(url('admin/' . $l[0])); ?>" class="<?= $adminActive === $k ? 'active' : '' ?>"><?= icon($l[2]) ?> <?php h($l[1]); ?></a><?php endforeach; ?></nav>
    <div class="a-nav-label">Contenu</div>
    <nav class="a-nav"><?php foreach ($nav['content'] as $k => $l): ?><a href="<?php h(url('admin/' . $l[0])); ?>" class="<?= $adminActive === $k ? 'active' : '' ?>"><?= icon($l[2]) ?> <?php h($l[1]); ?></a><?php endforeach; ?></nav>
    <div class="a-nav-label">Compte</div>
    <nav class="a-nav">
      <a href="<?php h(url('index.php')); ?>" target="_blank"><?= icon('eye') ?> Voir le site</a>
      <a href="<?php h(url('admin/logout.php')); ?>"><?= icon('logout') ?> Déconnexion</a>
    </nav>
  </aside>
  <div class="a-main">
    <header class="a-topbar">
      <button class="a-icon-btn a-menu-toggle" onclick="document.getElementById('aSidebar').classList.toggle('open')"><?= icon('menu') ?></button>
      <h1><?php h($adminTitle); ?></h1>
      <div class="spacer"></div>
      <div class="a-search"><?= icon('search') ?><input placeholder="Rechercher…" onkeydown="if(event.key==='Enter'&&this.value)location.href='<?php h(url('admin/books.php')); ?>?q='+encodeURIComponent(this.value)"></div>
      <button class="a-icon-btn" onclick="var d=document.documentElement,n=d.getAttribute('data-theme')==='dark'?'light':'dark';d.setAttribute('data-theme',n);localStorage.setItem('theme',n)"><?= icon('moon') ?></button>
      <span class="a-avatar"><?php h(initials($admin['name'] ?? 'A')); ?></span>
    </header>
    <main class="a-content">
      <?php foreach (get_flashes() as $f): ?><div class="a-alert" style="<?= $f['type']==='error'?'background:#f2d6d2;color:#a3302a':'' ?>"><?php h($f['message']); ?></div><?php endforeach; ?>
<?php
function admin_footer(): void { ?>
    </main>
  </div>
</div>
</body></html>
<?php }
