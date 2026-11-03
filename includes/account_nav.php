<?php
/** account_nav.php — Barre latérale de l'espace client (DRY). $active = clé. */
require_once __DIR__ . '/icons.php';
$active = $active ?? '';
$links = [
    'dashboard' => ['account.php', 'Tableau de bord', 'grid'],
    'orders'    => ['orders.php', 'Mes commandes', 'box'],
    'wishlist'  => ['../wishlist.php', 'Mes favoris', 'heart'],
    'addresses' => ['addresses.php', 'Mes adresses', 'pin'],
    'profile'   => ['profile.php', 'Mon profil', 'user'],
    'notifications' => ['notifications.php', 'Notifications', 'chat'],
];
?>
<nav class="account-nav">
  <?php foreach ($links as $key => $l): ?>
    <a href="<?php h(url('customer/' . $l[0])); ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= icon($l[2]) ?> <?php h($l[1]); ?></a>
  <?php endforeach; ?>
  <a href="<?php h(url('customer/logout.php')); ?>"><?= icon('logout') ?> Déconnexion</a>
</nav>
