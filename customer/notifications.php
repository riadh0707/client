<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_login();
$u = current_user();

// Notifications dérivées des commandes récentes (démo).
$orders = Database::all('SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC LIMIT 6', [$u['id']]);
$notifs = [];
foreach ($orders as $o) {
    $msg = [
        'pending'   => 'Votre commande a bien été reçue et est en attente de confirmation.',
        'confirmed' => 'Votre commande est confirmée et en préparation.',
        'shipped'   => 'Bonne nouvelle ! Votre commande a été expédiée.',
        'delivered' => 'Votre commande a été livrée. Merci pour votre confiance !',
        'cancelled' => 'Votre commande a été annulée.',
    ][$o['status']] ?? '';
    $notifs[] = ['icon' => 'box', 'title' => 'Commande ' . $o['reference'], 'msg' => $msg, 'date' => $o['created_at']];
}
$notifs[] = ['icon' => 'gift', 'title' => 'Points fidélité', 'msg' => 'Vous avez ' . $u['loyalty_points'] . ' points. Continuez pour débloquer vos récompenses !', 'date' => date('Y-m-d')];

$pageTitle = 'Notifications';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm">
  <div class="container">
    <div class="account-layout">
      <?php $active = 'notifications'; require INCLUDES_PATH . '/account_nav.php'; ?>
      <div>
        <h1 style="margin-bottom:18px">Notifications</h1>
        <div class="card-panel">
          <?php foreach ($notifs as $n): ?>
          <div class="review-item" style="display:flex;gap:14px;align-items:flex-start">
            <span class="review-avatar"><?= icon($n['icon']) ?></span>
            <div style="flex:1"><strong><?php h($n['title']); ?></strong><p style="color:var(--muted);margin:4px 0 0"><?php h($n['msg']); ?></p></div>
            <span style="color:var(--muted);font-size:.8rem"><?php h(time_ago($n['date'])); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
