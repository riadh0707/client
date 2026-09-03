<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_login();
$u = current_user();
$view = (int) input('view');
$detail = $view ? Database::first('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [$view, $u['id']]) : null;
$items = $detail ? Database::all('SELECT * FROM order_items WHERE order_id = ?', [$detail['id']]) : [];
$orders = Database::all('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC', [$u['id']]);
$pageTitle = 'Mes commandes';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm"><div class="container">
  <div class="account-layout">
    <?php $active = 'orders'; require INCLUDES_PATH . '/account_nav.php'; ?>
    <div>
    <?php if ($detail): ?>
      <a href="<?php h(url('customer/orders.php')); ?>" class="text-coffee" style="font-weight:600"><?= icon('arrow','flip') ?> Retour</a>
      <div class="card-panel" style="margin-top:14px">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px"><div><h2 style="margin:0"><?php h($detail['reference']); ?></h2><span style="color:var(--muted)"><?php h(fdate($detail['created_at'], 'd/m/Y à H:i')); ?></span></div><?= order_status_pill($detail['status']) ?></div>
        <div style="overflow-x:auto"><table class="table"><thead><tr><th>Livre</th><th>Prix</th><th></th></tr></thead><tbody>
        <?php foreach ($items as $it): $owned = has_book_access((int) $it['book_id']); $bk = Database::first('SELECT slug FROM books WHERE id=?', [$it['book_id']]); ?>
          <tr><td><?php h($it['title']); ?></td><td><?= money($it['price']) ?></td><td><?php if ($owned && $bk): ?><a class="btn btn-sm" href="<?php h(url('lire.php?slug=' . $bk['slug'])); ?>"><?= icon('book-open') ?> Lire</a><?php else: ?><span class="chip"><?= icon('lock') ?> En attente</span><?php endif; ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <div style="max-width:300px;margin-left:auto;margin-top:14px"><div class="summary-row" style="display:flex;justify-content:space-between;padding:6px 0"><span>Total</span><strong style="font-size:1.15rem;color:var(--text)"><?= money($detail['total']) ?></strong></div></div>
        <?php if ($detail['status'] !== 'paid'): ?><div class="chip" style="margin-top:12px;white-space:normal">⏳ Paiement BaridiMob en attente de validation par notre équipe.</div><?php endif; ?>
      </div>
    <?php else: ?>
      <h1 style="margin-bottom:18px">Mes commandes</h1>
      <?php if (empty($orders)): ?><div class="empty-state"><div class="ic"><?= icon('receipt') ?></div><h3>Aucune commande</h3><a href="<?php h(url('catalogue.php')); ?>" class="btn">Découvrir le catalogue</a></div>
      <?php else: ?>
        <div class="card-panel" style="overflow-x:auto"><table class="table"><thead><tr><th>Référence</th><th>Date</th><th>Total</th><th>Statut</th><th></th></tr></thead><tbody>
        <?php foreach ($orders as $o): ?><tr><td><strong><?php h($o['reference']); ?></strong></td><td><?php h(fdate($o['created_at'])); ?></td><td><?= money($o['total']) ?></td><td><?= order_status_pill($o['status']) ?></td><td><a href="<?php h(url('customer/orders.php?view=' . $o['id'])); ?>" class="text-coffee" style="font-weight:600">Détails →</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    <?php endif; ?>
    </div>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
