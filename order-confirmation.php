<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';

$order = Database::first('SELECT * FROM orders WHERE reference = ?', [input('ref')]);
if (!$order) { redirect('index.php'); }
$items = Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);

$pageTitle = 'Commande enregistrée';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:660px">
    <div class="card-panel center" style="padding:46px 40px">
      <div class="ic" style="width:88px;height:88px;margin:0 auto 20px;border-radius:50%;background:var(--surface-2);display:grid;place-items:center;color:var(--coffee)">
        <?= icon($order['status'] === 'paid' ? 'unlock' : 'clock') ?>
      </div>
      <?php if ($order['status'] === 'paid'): ?>
        <h1>Paiement validé !</h1>
        <p style="color:var(--muted)">Vos titres sont disponibles dans votre bibliothèque.</p>
      <?php else: ?>
        <h1>Merci, commande enregistrée !</h1>
        <p style="color:var(--muted)">Votre commande <strong class="text-coffee"><?php h($order['reference']); ?></strong> est <strong>en attente de validation</strong>. Dès que notre équipe confirme votre virement BaridiMob, vos titres s'ouvriront dans votre bibliothèque. Vous serez prévenu.</p>
      <?php endif; ?>

      <div class="card-panel" style="text-align:left;margin:26px 0;background:var(--surface-2)">
        <?php foreach ($items as $it): ?><div class="summary-row"><span><?php h($it['title']); ?></span><strong><?= money($it['price']) ?></strong></div><?php endforeach; ?>
        <?php if ($order['discount'] > 0): ?><div class="summary-row"><span>Réduction</span><strong style="color:var(--success)">-<?= money($order['discount']) ?></strong></div><?php endif; ?>
        <div class="summary-row total"><span>Total</span><strong><?= money($order['total']) ?></strong></div>
        <div class="chip" style="margin-top:12px"><?= order_status_pill($order['status']) ?></div>
      </div>

      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?php h(url('customer/library.php')); ?>" class="btn"><?= icon('library') ?> Ma bibliothèque</a>
        <a href="<?php h(url('customer/orders.php')); ?>" class="btn btn-outline">Suivre ma commande</a>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
