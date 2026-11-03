<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_login();

$u = current_user();
$view = (int) input('view');
$detail = null; $items = [];
if ($view) {
    $detail = Database::first('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [$view, $u['id']]);
    if ($detail) { $items = Database::all('SELECT * FROM order_items WHERE order_id = ?', [$detail['id']]); }
}
$orders = Database::all('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC', [$u['id']]);

$pageTitle = 'Mes commandes';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm">
  <div class="container">
    <div class="account-layout">
      <?php $active = 'orders'; require INCLUDES_PATH . '/account_nav.php'; ?>
      <div>
        <?php if ($detail): ?>
          <a href="<?php h(url('customer/orders.php')); ?>" class="text-rose" style="font-weight:600"><?= icon('arrow','flip') ?> Retour aux commandes</a>
          <div class="card-panel" style="margin-top:14px">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
              <div><h2 style="margin:0"><?php h($detail['reference']); ?></h2><span style="color:var(--muted)">Passée le <?php h(fdate($detail['created_at'], 'd/m/Y à H:i')); ?></span></div>
              <?= order_status_pill($detail['status']) ?>
            </div>
            <div style="overflow-x:auto"><table class="table">
              <thead><tr><th>Produit</th><th>Prix</th><th>Qté</th><th>Total</th></tr></thead>
              <tbody>
              <?php foreach ($items as $it): ?>
                <tr><td><?php h($it['name']); ?></td><td><?= money($it['price']) ?></td><td><?= $it['qty'] ?></td><td><?= money($it['price']*$it['qty']) ?></td></tr>
              <?php endforeach; ?>
              </tbody>
            </table></div>
            <div style="max-width:320px;margin-left:auto;margin-top:16px">
              <div class="summary-row"><span>Sous-total</span><strong><?= money($detail['subtotal']) ?></strong></div>
              <div class="summary-row"><span>Livraison</span><strong><?= $detail['shipping']==0?'Offerte':money($detail['shipping']) ?></strong></div>
              <div class="summary-row total"><span>Total</span><strong><?= money($detail['total']) ?></strong></div>
            </div>
            <div class="chip" style="margin-top:14px"><?= icon('pin') ?> <?php h($detail['address']); ?>, <?php h($detail['wilaya']); ?></div>
          </div>
        <?php else: ?>
          <h1 style="margin-bottom:18px">Mes commandes</h1>
          <?php if (empty($orders)): ?>
            <div class="empty-state"><div class="ic"><?= icon('box') ?></div><h3>Aucune commande</h3><p style="color:var(--muted)">Vos futures commandes apparaîtront ici.</p><a href="<?php h(url('shop.php')); ?>" class="btn">Commencer mes achats</a></div>
          <?php else: ?>
            <div class="card-panel" style="overflow-x:auto"><table class="table">
              <thead><tr><th>Référence</th><th>Date</th><th>Articles</th><th>Total</th><th>Statut</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($orders as $o):
                $count = (int) Database::scalar('SELECT SUM(qty) FROM order_items WHERE order_id = ?', [$o['id']]); ?>
                <tr>
                  <td><strong><?php h($o['reference']); ?></strong></td>
                  <td><?php h(fdate($o['created_at'])); ?></td>
                  <td><?= $count ?> article(s)</td>
                  <td><?= money($o['total']) ?></td>
                  <td><?= order_status_pill($o['status']) ?></td>
                  <td><a href="<?php h(url('customer/orders.php?view=' . $o['id'])); ?>" class="text-rose" style="font-weight:600">Détails →</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
