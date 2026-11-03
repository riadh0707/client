<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';

$ref = input('ref');
$order = Database::first('SELECT * FROM orders WHERE reference = ?', [$ref]);
if (!$order) { redirect('index.php'); }
$items = Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);

$pageTitle = 'Commande confirmée';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:680px">
    <div class="card-panel center" style="padding:48px 40px">
      <div class="ic" style="width:88px;height:88px;margin:0 auto 20px;border-radius:50%;background:var(--blush);display:grid;place-items:center;color:var(--success)">
        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      <h1>Merci pour votre commande !</h1>
      <p style="color:var(--muted)">Votre commande <strong style="color:var(--rose)"><?php h($order['reference']); ?></strong> a bien été enregistrée. Nous vous contacterons rapidement au <strong><?php h($order['phone']); ?></strong> pour confirmer la livraison.</p>

      <div class="card-panel" style="text-align:left;margin:26px 0;background:var(--surface-2)">
        <?php foreach ($items as $it): ?>
        <div class="summary-row"><span><?php h($it['name']); ?> ×<?= $it['qty'] ?></span><strong><?= money($it['price']*$it['qty']) ?></strong></div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Livraison</span><strong><?= $order['shipping']==0?'Offerte':money($order['shipping']) ?></strong></div>
        <?php if ($order['discount']>0): ?><div class="summary-row"><span>Réduction</span><strong style="color:var(--success)">-<?= money($order['discount']) ?></strong></div><?php endif; ?>
        <div class="summary-row total"><span>Total</span><strong><?= money($order['total']) ?></strong></div>
      </div>

      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?php h(url('shop.php')); ?>" class="btn">Continuer mes achats</a>
        <?php if (is_logged_in()): ?><a href="<?php h(url('customer/orders.php')); ?>" class="btn btn-outline">Suivre ma commande</a><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
