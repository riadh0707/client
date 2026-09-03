<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

$revenue = (float) Database::scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'paid'");
$orderCount = (int) Database::scalar('SELECT COUNT(*) FROM orders');
$custCount = (int) Database::scalar('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL');
$bookCount = (int) Database::scalar("SELECT COUNT(*) FROM books WHERE deleted_at IS NULL");
$toValidate = (int) Database::scalar("SELECT COUNT(*) FROM orders WHERE status IN ('pending','awaiting')");
$pendingReviews = (int) Database::scalar("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");

$driver = Database::driver();
$monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
$rows = Database::all("SELECT $monthExpr AS m, SUM(total) t FROM orders WHERE status='paid' GROUP BY m ORDER BY m");
$byMonth = []; foreach ($rows as $r) { $byMonth[$r['m']] = (float) $r['t']; }
$months = [];
for ($i = 5; $i >= 0; $i--) { $k = date('Y-m', strtotime("-$i months")); $months[$k] = $byMonth[$k] ?? 0; }
$maxMonth = max(1, max($months));

$statusRows = Database::all('SELECT status, COUNT(*) c FROM orders GROUP BY status');
$statusData = []; foreach ($statusRows as $s) { $statusData[$s['status']] = (int) $s['c']; }
$statusColors = ['pending' => '#c08a2e', 'awaiting' => '#6a5aa0', 'paid' => '#4f7d3f', 'cancelled' => '#a3302a'];
$totalStatus = array_sum($statusData) ?: 1;
$gradient = []; $acc = 0;
foreach ($statusColors as $st => $col) { $val = $statusData[$st] ?? 0; if (!$val) continue; $start = $acc / $totalStatus * 100; $acc += $val; $end = $acc / $totalStatus * 100; $gradient[] = "$col {$start}% {$end}%"; }
$donutBg = 'conic-gradient(' . implode(',', $gradient ?: ['#ddd 0 100%']) . ')';

$recentOrders = Database::all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6');
$topBooks = Database::all("SELECT b.title, COUNT(oi.id) sold FROM order_items oi JOIN books b ON b.id = oi.book_id JOIN orders o ON o.id=oi.order_id WHERE o.status='paid' GROUP BY oi.book_id ORDER BY sold DESC LIMIT 5");
$statusLabels = ['pending' => 'À payer', 'awaiting' => 'En validation', 'paid' => 'Payée', 'cancelled' => 'Annulée'];

$adminActive = 'dashboard';
$adminTitle = 'Tableau de bord';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<?php if ($toValidate || $pendingReviews): ?>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px">
  <?php if ($toValidate): ?><a href="<?php h(url('admin/orders.php?status=awaiting')); ?>" class="a-tag" style="background:#f5e6c8;color:#8a5a1e;padding:10px 16px"><?= icon('receipt') ?> <?= $toValidate ?> commande(s) à valider</a><?php endif; ?>
  <?php if ($pendingReviews): ?><a href="<?php h(url('admin/reviews.php?status=pending')); ?>" class="a-tag" style="background:#e2ddf1;color:#5a4a9a;padding:10px 16px"><?= icon('star') ?> <?= $pendingReviews ?> avis à modérer</a><?php endif; ?>
</div>
<?php endif; ?>

<div class="a-cards">
  <div class="a-card"><div class="a-ic" style="background:linear-gradient(135deg,#8a5a34,#6b4726)"><?= icon('wallet') ?></div><div class="a-val"><?= money($revenue) ?></div><div class="a-lbl">Revenus (payés)</div><div class="a-trend up">▲ +14%</div></div>
  <div class="a-card"><div class="a-ic" style="background:linear-gradient(135deg,#6a5aa0,#4a3e7a)"><?= icon('receipt') ?></div><div class="a-val"><?= $orderCount ?></div><div class="a-lbl">Commandes</div><div class="a-trend up">▲ +9%</div></div>
  <div class="a-card"><div class="a-ic" style="background:linear-gradient(135deg,#4f7d3f,#3a5e2e)"><?= icon('user') ?></div><div class="a-val"><?= $custCount ?></div><div class="a-lbl">Clients</div><div class="a-trend up">▲ +6%</div></div>
  <div class="a-card"><div class="a-ic" style="background:linear-gradient(135deg,#a8863f,#8a6a2f)"><?= icon('book') ?></div><div class="a-val"><?= $bookCount ?></div><div class="a-lbl">Livres publiés</div><div class="a-trend">Catalogue</div></div>
</div>

<div class="a-grid-2">
  <div class="a-panel">
    <div class="a-panel-head"><h2>Revenus sur 6 mois</h2></div>
    <div class="chart"><?php foreach ($months as $key => $v): $hh = round($v / $maxMonth * 100); ?><div class="bar" style="height:<?= max(4,$hh) ?>%"><em><?= money($v) ?></em><span><?= date('M', strtotime($key . '-01')) ?></span></div><?php endforeach; ?></div>
    <div style="height:24px"></div>
  </div>
  <div class="a-panel">
    <div class="a-panel-head"><h3>Commandes par statut</h3></div>
    <div class="donut" style="background:<?= $donutBg ?>"></div>
    <div class="donut-legend"><?php foreach ($statusColors as $st => $col): if (empty($statusData[$st])) continue; ?><div><span class="dot" style="background:<?= $col ?>"></span> <?php h($statusLabels[$st]); ?> <strong style="margin-left:auto"><?= $statusData[$st] ?></strong></div><?php endforeach; ?></div>
  </div>
</div>

<div class="a-grid-2">
  <div class="a-panel">
    <div class="a-panel-head"><h3>Commandes récentes</h3><a href="<?php h(url('admin/orders.php')); ?>" class="a-btn ghost sm">Tout voir</a></div>
    <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Réf.</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead><tbody>
    <?php foreach ($recentOrders as $o): ?><tr><td><a href="<?php h(url('admin/orders.php?view=' . $o['id'])); ?>"><strong><?php h($o['reference']); ?></strong></a></td><td><?php h($o['customer_name']); ?></td><td><?= money($o['total']) ?></td><td><span class="a-pill st-<?php h($o['status']); ?>"><?php h($statusLabels[$o['status']] ?? $o['status']); ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="a-panel">
    <div class="a-panel-head"><h3>Livres les plus vendus</h3></div>
    <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Livre</th><th>Ventes</th></tr></thead><tbody>
    <?php foreach ($topBooks as $b): ?><tr><td><?php h(excerpt($b['title'], 34)); ?></td><td><strong><?= (int) $b['sold'] ?></strong></td></tr><?php endforeach; ?>
    <?php if (empty($topBooks)): ?><tr><td colspan="2" class="a-empty">Aucune vente validée.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</div>
<?php admin_footer(); ?>
