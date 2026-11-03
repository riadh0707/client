<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_login();

$u = current_user();
$orders = Database::all('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC', [$u['id']]);
$totalSpent = array_sum(array_map(fn($o) => $o['status'] !== 'cancelled' ? $o['total'] : 0, $orders));
$wishCount = count($_SESSION['wishlist'] ?? []);

$pageTitle = 'Mon compte';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm">
  <div class="container">
    <div class="account-layout">
      <?php $active = 'dashboard'; require INCLUDES_PATH . '/account_nav.php'; ?>
      <div>
        <div class="card-panel hello" style="margin-bottom:22px">
          <h1 style="margin-bottom:4px">Bonjour, <?php h($u['first_name']); ?></h1>
          <p style="color:var(--muted);margin:0">Membre depuis <?php h(fdate($u['created_at'], 'F Y')); ?> · <?= (int) $u['loyalty_points'] ?> points fidélité</p>
        </div>

        <div class="stat-cards">
          <div class="stat-card"><div class="sv"><?= count($orders) ?></div><div class="sl">Commandes passées</div></div>
          <div class="stat-card"><div class="sv"><?= money($totalSpent) ?></div><div class="sl">Total dépensé</div></div>
          <div class="stat-card"><div class="sv"><?= $wishCount ?></div><div class="sl">Produits favoris</div></div>
        </div>

        <!-- Fidélité -->
        <div class="card-panel" style="margin-bottom:22px">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div><h3 style="margin:0"><?= icon('award') ?> Programme fidélité</h3><p style="color:var(--muted);margin:4px 0 0">Plus que <?= max(0, 1000 - $u['loyalty_points']) ?> points pour un bon de -1000 DA</p></div>
            <strong style="font-size:1.6rem;color:var(--rose)"><?= (int) $u['loyalty_points'] ?> pts</strong>
          </div>
          <div class="ship-progress" style="margin-top:12px"><i style="width:<?= min(100, $u['loyalty_points'] / 10) ?>%"></i></div>
        </div>

        <div class="card-panel">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 style="margin:0">Dernières commandes</h3>
            <a href="<?php h(url('customer/orders.php')); ?>" class="text-rose" style="font-weight:600;font-size:.88rem">Tout voir</a>
          </div>
          <?php if (empty($orders)): ?>
            <p style="color:var(--muted)">Vous n'avez pas encore passé de commande. <a href="<?php h(url('shop.php')); ?>" class="text-rose">Découvrir la boutique</a></p>
          <?php else: ?>
          <div style="overflow-x:auto">
            <table class="table">
              <thead><tr><th>Référence</th><th>Date</th><th>Total</th><th>Statut</th></tr></thead>
              <tbody>
              <?php foreach (array_slice($orders, 0, 5) as $o): ?>
                <tr>
                  <td><strong><?php h($o['reference']); ?></strong></td>
                  <td><?php h(fdate($o['created_at'])); ?></td>
                  <td><?= money($o['total']) ?></td>
                  <td><?= order_status_pill($o['status']) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
