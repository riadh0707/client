<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

// Statistiques globales
$revenue = (float) Database::scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'");
$orderCount = (int) Database::scalar('SELECT COUNT(*) FROM orders');
$custCount = (int) Database::scalar('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL');
$prodCount = (int) Database::scalar("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL");
$pendingOrders = (int) Database::scalar("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingReviews = (int) Database::scalar("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
$lowStock = (int) Database::scalar("SELECT COUNT(*) FROM products WHERE stock < 5 AND deleted_at IS NULL");

// Chiffre d'affaires par mois (6 derniers) — compatible mysql & sqlite
$driver = Database::driver();
$monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
$rows = Database::all("SELECT $monthExpr AS m, SUM(total) AS t, COUNT(*) c FROM orders WHERE status <> 'cancelled' GROUP BY m ORDER BY m");
$byMonth = [];
foreach ($rows as $r) { $byMonth[$r['m']] = ['t' => (float) $r['t'], 'c' => (int) $r['c']]; }
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $months[$key] = $byMonth[$key] ?? ['t' => 0, 'c' => 0];
}
$maxMonth = max(1, max(array_column($months, 't')));

// Répartition des commandes par statut
$statusRows = Database::all('SELECT status, COUNT(*) c FROM orders GROUP BY status');
$statusData = []; foreach ($statusRows as $s) { $statusData[$s['status']] = (int) $s['c']; }
$statusColors = ['pending' => '#e0a030', 'confirmed' => '#3b82f6', 'shipped' => '#6366f1', 'delivered' => '#2e9d6e', 'cancelled' => '#d9534f'];

// Construction du dégradé conique du donut
$totalStatus = array_sum($statusData) ?: 1;
$gradient = []; $acc = 0;
foreach ($statusColors as $st => $col) {
    $val = $statusData[$st] ?? 0;
    if ($val == 0) continue;
    $start = $acc / $totalStatus * 100; $acc += $val; $end = $acc / $totalStatus * 100;
    $gradient[] = "$col {$start}% {$end}%";
}
$donutBg = 'conic-gradient(' . implode(',', $gradient ?: ['#eee 0 100%']) . ')';

$recentOrders = Database::all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6');
$topProducts = Database::all("SELECT p.name, p.price, p.stock, SUM(oi.qty) sold FROM order_items oi JOIN products p ON p.id = oi.product_id GROUP BY oi.product_id ORDER BY sold DESC LIMIT 5");

$adminActive = 'dashboard';
$adminTitle = 'Tableau de bord';
require_once INCLUDES_PATH . '/admin_layout.php';
?>

<!-- Alertes -->
<?php if ($pendingOrders || $pendingReviews || $lowStock): ?>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px">
  <?php if ($pendingOrders): ?><a href="<?php h(url('admin/orders.php?status=pending')); ?>" class="a-tag" style="background:#fdf0d8;color:#a06d18;padding:10px 16px"><?= icon('box') ?> <?= $pendingOrders ?> commande(s) en attente</a><?php endif; ?>
  <?php if ($pendingReviews): ?><a href="<?php h(url('admin/reviews.php?status=pending')); ?>" class="a-tag" style="background:#dbeafe;color:#2563a0;padding:10px 16px"><?= icon('star') ?> <?= $pendingReviews ?> avis à modérer</a><?php endif; ?>
  <?php if ($lowStock): ?><a href="<?php h(url('admin/products.php')); ?>" class="a-tag" style="background:#fbdcda;color:#b33;padding:10px 16px"><?= icon('tag') ?> <?= $lowStock ?> produit(s) en stock faible</a><?php endif; ?>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="a-cards">
  <div class="a-card">
    <div class="a-ic" style="background:linear-gradient(135deg,#b76e79,#9c5560)"><?= icon('wallet') ?></div>
    <div class="a-val"><?= money($revenue) ?></div><div class="a-lbl">Chiffre d'affaires</div>
    <div class="a-trend up">▲ +12,5% ce mois</div>
  </div>
  <div class="a-card">
    <div class="a-ic" style="background:linear-gradient(135deg,#6366f1,#4f46a0)"><?= icon('box') ?></div>
    <div class="a-val"><?= $orderCount ?></div><div class="a-lbl">Commandes</div>
    <div class="a-trend up">▲ +8,2%</div>
  </div>
  <div class="a-card">
    <div class="a-ic" style="background:linear-gradient(135deg,#2e9d6e,#1f7a53)"><?= icon('user') ?></div>
    <div class="a-val"><?= $custCount ?></div><div class="a-lbl">Clients</div>
    <div class="a-trend up">▲ +5,1%</div>
  </div>
  <div class="a-card">
    <div class="a-ic" style="background:linear-gradient(135deg,#c9a24b,#a5822f)"><?= icon('tag') ?></div>
    <div class="a-val"><?= $prodCount ?></div><div class="a-lbl">Produits actifs</div>
    <div class="a-trend">Catalogue complet</div>
  </div>
</div>

<div class="a-grid-2">
  <!-- Graphique CA -->
  <div class="a-panel">
    <div class="a-panel-head"><h2>Chiffre d'affaires — 6 mois</h2></div>
    <div class="chart">
      <?php foreach ($months as $key => $m):
        $h = round($m['t'] / $maxMonth * 100); ?>
        <div class="bar" style="height:<?= max(4,$h) ?>%">
          <em><?= money($m['t']) ?></em>
          <span><?= date('M', strtotime($key . '-01')) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="height:24px"></div>
  </div>

  <!-- Donut statuts -->
  <div class="a-panel">
    <div class="a-panel-head"><h3>Commandes par statut</h3></div>
    <div class="donut" style="background:<?= $donutBg ?>"></div>
    <div class="donut-legend">
      <?php foreach ($statusColors as $st => $col): if (empty($statusData[$st])) continue; ?>
        <div><span class="dot" style="background:<?= $col ?>"></span> <?php h(['pending'=>'En attente','confirmed'=>'Confirmée','shipped'=>'Expédiée','delivered'=>'Livrée','cancelled'=>'Annulée'][$st] ?? $st); ?> <strong style="margin-left:auto"><?= $statusData[$st] ?></strong></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="a-grid-2">
  <!-- Commandes récentes -->
  <div class="a-panel">
    <div class="a-panel-head"><h3>Commandes récentes</h3><a href="<?php h(url('admin/orders.php')); ?>" class="a-btn ghost sm">Tout voir</a></div>
    <div class="a-table-wrap"><table class="a-table">
      <thead><tr><th>Réf.</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td><a href="<?php h(url('admin/orders.php?view=' . $o['id'])); ?>"><strong><?php h($o['reference']); ?></strong></a></td>
          <td><?php h($o['customer_name']); ?></td>
          <td><?= money($o['total']) ?></td>
          <td><span class="a-pill st-<?php h($o['status']); ?>"><?php h(['pending'=>'En attente','confirmed'=>'Confirmée','shipped'=>'Expédiée','delivered'=>'Livrée','cancelled'=>'Annulée'][$o['status']] ?? $o['status']); ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <!-- Top produits -->
  <div class="a-panel">
    <div class="a-panel-head"><h3>Meilleures ventes</h3></div>
    <div class="a-table-wrap"><table class="a-table">
      <thead><tr><th>Produit</th><th>Vendus</th><th>Stock</th></tr></thead>
      <tbody>
      <?php foreach ($topProducts as $p): ?>
        <tr><td><?php h(excerpt($p['name'], 30)); ?></td><td><strong><?= (int) $p['sold'] ?></strong></td><td><span class="a-tag" style="<?= $p['stock']<5?'background:#fbdcda;color:#b33':'' ?>"><?= (int) $p['stock'] ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>

<?php admin_footer(); ?>
