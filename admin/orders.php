<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

// Export CSV
if (input('export') === 'csv') {
    $rows = Database::all('SELECT reference, customer_name, phone, wilaya, total, payment_method, status, created_at FROM orders ORDER BY created_at DESC');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="commandes-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Référence', 'Client', 'Téléphone', 'Wilaya', 'Total', 'Paiement', 'Statut', 'Date']);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out); exit;
}

if (is_post()) {
    csrf_check();
    if (input('action') === 'status') {
        Database::run('UPDATE orders SET status = ? WHERE id = ?', [input('status'), (int) input('id')]);
        flash('Statut de la commande mis à jour.');
    }
    redirect('admin/orders.php' . (input('view') ? '?view=' . (int) input('view') : ''));
}

$view = (int) input('view');
$detail = $view ? Database::first('SELECT * FROM orders WHERE id = ?', [$view]) : null;
$items = $detail ? Database::all('SELECT * FROM order_items WHERE order_id = ?', [$detail['id']]) : [];

$statusFilter = input('status');
$where = '1=1'; $params = [];
if ($statusFilter) { $where .= ' AND status = ?'; $params[] = $statusFilter; }
$perPage = 15;
$total = (int) Database::scalar("SELECT COUNT(*) FROM orders WHERE $where", $params);
$pg = paginate($total, $perPage, (int) input('page', '1'));
$orders = Database::all("SELECT * FROM orders WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET {$pg['offset']}", $params);

$statuses = ['pending'=>'En attente','confirmed'=>'Confirmée','shipped'=>'Expédiée','delivered'=>'Livrée','cancelled'=>'Annulée'];

$adminActive = 'orders';
$adminTitle = 'Commandes';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<?php if ($detail): ?>
  <a href="<?php h(url('admin/orders.php')); ?>" class="a-btn ghost sm" style="margin-bottom:16px"><?= icon('arrow','flip') ?> Retour</a>
  <div class="a-grid-2">
    <div class="a-panel">
      <div class="a-panel-head"><h2><?php h($detail['reference']); ?></h2><span class="a-pill st-<?php h($detail['status']); ?>"><?php h($statuses[$detail['status']] ?? $detail['status']); ?></span></div>
      <div class="a-table-wrap"><table class="a-table">
        <thead><tr><th>Produit</th><th>PU</th><th>Qté</th><th>Total</th></tr></thead>
        <tbody><?php foreach ($items as $it): ?><tr><td><?php h($it['name']); ?></td><td><?= money($it['price']) ?></td><td><?= $it['qty'] ?></td><td><?= money($it['price']*$it['qty']) ?></td></tr><?php endforeach; ?></tbody>
      </table></div>
      <div style="max-width:300px;margin-left:auto;margin-top:14px">
        <div class="summary-row" style="display:flex;justify-content:space-between;padding:6px 0"><span>Sous-total</span><strong><?= money($detail['subtotal']) ?></strong></div>
        <div class="summary-row" style="display:flex;justify-content:space-between;padding:6px 0"><span>Livraison</span><strong><?= $detail['shipping']==0?'Offerte':money($detail['shipping']) ?></strong></div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--a-border);font-size:1.15rem;font-weight:700"><span>Total</span><strong><?= money($detail['total']) ?></strong></div>
      </div>
    </div>
    <div>
      <div class="a-panel">
        <h3 style="margin-bottom:14px">Client</h3>
        <p style="line-height:1.9;margin:0"><strong><?php h($detail['customer_name']); ?></strong><br><?= icon('phone') ?> <?php h($detail['phone']); ?><br><?= icon('mail') ?> <?php h($detail['email'] ?: '—'); ?><br><?= icon('pin') ?> <?php h($detail['address']); ?>, <?php h($detail['wilaya']); ?></p>
        <?php if ($detail['note']): ?><div class="a-tag" style="margin-top:10px;white-space:normal">📝 <?php h($detail['note']); ?></div><?php endif; ?>
      </div>
      <div class="a-panel">
        <h3 style="margin-bottom:14px">Mettre à jour le statut</h3>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $detail['id'] ?>"><input type="hidden" name="view" value="<?= $detail['id'] ?>">
          <div class="a-field"><select name="status">
            <?php foreach ($statuses as $k => $lbl): ?><option value="<?= $k ?>" <?= $detail['status']===$k?'selected':'' ?>><?php h($lbl); ?></option><?php endforeach; ?>
          </select></div>
          <button class="a-btn" style="width:100%;justify-content:center">Enregistrer</button>
        </form>
        <div class="a-tag" style="margin-top:12px">Paiement : <?php h(strtoupper($detail['payment_method'])); ?></div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="a-panel">
    <div class="a-panel-head">
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="<?php h(url('admin/orders.php')); ?>" class="a-btn <?= !$statusFilter?'':'ghost' ?> sm">Toutes</a>
        <?php foreach ($statuses as $k => $lbl): ?><a href="<?php h(url('admin/orders.php?status=' . $k)); ?>" class="a-btn <?= $statusFilter===$k?'':'ghost' ?> sm"><?php h($lbl); ?></a><?php endforeach; ?>
      </div>
      <a href="<?php h(url('admin/orders.php?export=csv')); ?>" class="a-btn ghost sm"><?= icon('box') ?> Exporter CSV</a>
    </div>
    <div class="a-table-wrap"><table class="a-table">
      <thead><tr><th>Référence</th><th>Client</th><th>Wilaya</th><th>Total</th><th>Paiement</th><th>Statut</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?php h($o['reference']); ?></strong></td>
          <td><?php h($o['customer_name']); ?></td>
          <td><?php h($o['wilaya']); ?></td>
          <td><strong><?= money($o['total']) ?></strong></td>
          <td><span class="a-tag"><?php h(strtoupper($o['payment_method'])); ?></span></td>
          <td><span class="a-pill st-<?php h($o['status']); ?>"><?php h($statuses[$o['status']] ?? $o['status']); ?></span></td>
          <td><?php h(fdate($o['created_at'])); ?></td>
          <td><a class="a-iconlink" href="<?php h(url('admin/orders.php?view=' . $o['id'])); ?>"><?= icon('eye') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php require INCLUDES_PATH . '/admin_pagination.php'; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
