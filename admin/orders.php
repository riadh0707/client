<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_once INCLUDES_PATH . '/payment.php';
require_admin();

if (input('export') === 'csv') {
    $rows = Database::all('SELECT reference, customer_name, phone, total, payment_ref, status, created_at FROM orders ORDER BY created_at DESC');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="commandes-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Référence', 'Client', 'Téléphone', 'Total', 'Réf. BaridiMob', 'Statut', 'Date']);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out); exit;
}

if (is_post()) {
    csrf_check();
    $oid = (int) input('id');
    switch (input('action')) {
        case 'validate': mark_order_paid($oid); flash('Paiement validé, accès aux titres débloqué pour le client.'); break;
        case 'cancel':   Database::run("UPDATE orders SET status='cancelled' WHERE id=?", [$oid]); flash('Commande annulée.'); break;
        case 'reopen':   Database::run("UPDATE orders SET status='awaiting' WHERE id=?", [$oid]); flash('Commande remise en attente.'); break;
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
$statuses = ['pending' => 'À payer', 'awaiting' => 'En validation', 'paid' => 'Payée', 'cancelled' => 'Annulée'];

$adminActive = 'orders';
$adminTitle = 'Commandes';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<?php if ($detail): ?>
  <a href="<?php h(url('admin/orders.php')); ?>" class="a-btn ghost sm" style="margin-bottom:16px"><?= icon('arrow','flip') ?> Retour</a>
  <div class="a-grid-2">
    <div class="a-panel">
      <div class="a-panel-head"><h2><?php h($detail['reference']); ?></h2><span class="a-pill st-<?php h($detail['status']); ?>"><?php h($statuses[$detail['status']] ?? $detail['status']); ?></span></div>
      <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Livre</th><th>Prix</th></tr></thead><tbody><?php foreach ($items as $it): ?><tr><td><?php h($it['title']); ?></td><td><?= money($it['price']) ?></td></tr><?php endforeach; ?></tbody></table></div>
      <div style="max-width:300px;margin-left:auto;margin-top:14px">
        <div style="display:flex;justify-content:space-between;padding:6px 0"><span>Sous-total</span><strong><?= money($detail['subtotal']) ?></strong></div>
        <?php if ($detail['discount'] > 0): ?><div style="display:flex;justify-content:space-between;padding:6px 0"><span>Réduction</span><strong>-<?= money($detail['discount']) ?></strong></div><?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--a-border);font-size:1.15rem;font-weight:700"><span>Total</span><strong><?= money($detail['total']) ?></strong></div>
      </div>
    </div>
    <div>
      <div class="a-panel">
        <h3 style="margin-bottom:14px">Client</h3>
        <p style="line-height:1.9;margin:0"><strong><?php h($detail['customer_name']); ?></strong><br><?= icon('phone') ?> <?php h($detail['phone']); ?><br><?= icon('mail') ?> <?php h($detail['email'] ?: 'Non renseigné'); ?></p>
      </div>
      <div class="a-panel">
        <h3 style="margin-bottom:14px"><?= icon('wallet') ?> Paiement BaridiMob</h3>
        <p style="margin:0 0 8px"><span class="a-tag">Réf. transaction</span><br><strong style="font-family:monospace;font-size:1.05rem"><?= $detail['payment_ref'] ? e($detail['payment_ref']) : 'Non fournie' ?></strong></p>
        <?php if ($detail['receipt_file'] && is_file(UPLOADS_PATH . '/receipts/' . $detail['receipt_file'])): ?>
          <a href="<?php h(url('uploads/receipts/' . $detail['receipt_file'])); ?>" target="_blank" class="a-btn ghost sm"><?= icon('eye') ?> Voir le reçu</a>
        <?php endif; ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
          <?php if ($detail['status'] !== 'paid'): ?>
            <form method="post" onsubmit="return confirm('Confirmer la réception du paiement et débloquer l\'accès ?')"><?= csrf_field() ?><input type="hidden" name="action" value="validate"><input type="hidden" name="id" value="<?= $detail['id'] ?>"><input type="hidden" name="view" value="<?= $detail['id'] ?>"><button class="a-btn"><?= icon('check') ?> Valider le paiement</button></form>
          <?php else: ?>
            <span class="a-pill st-paid" style="padding:8px 14px"><?= icon('unlock') ?> Accès débloqué</span>
          <?php endif; ?>
          <?php if ($detail['status'] !== 'cancelled'): ?><form method="post" onsubmit="return confirm('Annuler cette commande ?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $detail['id'] ?>"><input type="hidden" name="view" value="<?= $detail['id'] ?>"><button class="a-btn danger sm"><?= icon('close') ?> Annuler</button></form><?php endif; ?>
        </div>
        <p style="color:var(--a-muted);font-size:.78rem;margin-top:12px">💡 Vérifiez la réception du virement sur votre compte BaridiMob avant de valider.</p>
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
      <a href="<?php h(url('admin/orders.php?export=csv')); ?>" class="a-btn ghost sm"><?= icon('download') ?> Export CSV</a>
    </div>
    <div class="a-table-wrap"><table class="a-table">
      <thead><tr><th>Référence</th><th>Client</th><th>Total</th><th>Réf. BaridiMob</th><th>Statut</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?php h($o['reference']); ?></strong></td>
          <td><?php h($o['customer_name']); ?></td>
          <td><strong><?= money($o['total']) ?></strong></td>
          <td><?php h($o['payment_ref'] ?: '-'); ?></td>
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
