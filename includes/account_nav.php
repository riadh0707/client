<?php
/** account_nav.php — Barre latérale espace client. $active = clé. */
require_once __DIR__ . '/icons.php';
$active = $active ?? '';
$links = [
    'library'  => ['library.php', 'Ma bibliothèque', 'library'],
    'orders'   => ['orders.php', 'Mes commandes', 'receipt'],
    'wishlist' => ['../wishlist.php', 'Ma liste de lecture', 'bookmark'],
    'profile'  => ['profile.php', 'Mon profil', 'user'],
];
?>
<nav class="account-nav">
  <?php foreach ($links as $key => $l): ?>
    <a href="<?php h(url('customer/' . $l[0])); ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= icon($l[2]) ?> <?php h($l[1]); ?></a>
  <?php endforeach; ?>
  <a href="<?php h(url('customer/logout.php')); ?>"><?= icon('logout') ?> Déconnexion</a>
</nav>
