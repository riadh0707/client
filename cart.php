<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$books = books_by_ids(array_keys(cart()));
$subtotal = 0;
foreach ($books as $b) { $subtotal += (float) $b['price']; }
$discount = min((float) ($_SESSION['coupon']['amount'] ?? 0), $subtotal);
$total = max(0, $subtotal - $discount);

$pageTitle = 'Mon panier';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Panier']]) ?></div>
<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:24px">Mon panier</h1>
    <?php if (empty($books)): ?>
      <div class="empty-state"><div class="ic"><?= icon('cart') ?></div><h3>Votre panier est vide</h3><p style="color:var(--muted)">Parcourez le catalogue et ajoutez vos titres.</p><a href="<?php h(url('catalogue.php')); ?>" class="btn btn-lg">Explorer le catalogue <?= icon('arrow') ?></a></div>
    <?php else: ?>
      <div class="cart-layout">
        <div class="cart-items" id="cartItems">
          <?php foreach ($books as $b): ?>
          <div class="cart-row" data-id="<?= (int) $b['id'] ?>">
            <img src="<?php h(upload_url($b['cover_image'] ?? null, 'covers', $b['title'])); ?>" alt="<?php h($b['title']); ?>">
            <div>
              <a href="<?php h(url('livre.php?slug=' . $b['slug'])); ?>" class="ci-title"><?php h($b['title']); ?></a>
              <div class="ci-meta">par <?php h($b['author']); ?> · <?= (int) $b['pages_count'] ?> pages</div>
            </div>
            <div style="text-align:right">
              <div class="ci-price"><?= e(book_price_label($b)) ?></div>
              <button class="cart-remove" onclick="cartRemove(<?= (int)$b['id'] ?>)"><?= icon('trash') ?> Retirer</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <aside class="cart-summary">
          <h3 style="margin-bottom:16px">Récapitulatif</h3>
          <div class="coupon-row">
            <input type="text" id="couponInput" placeholder="Code promo" value="<?= e($_SESSION['coupon']['code'] ?? '') ?>">
            <button class="btn btn-sm" onclick="applyCoupon()">Appliquer</button>
          </div>
          <div class="summary-row"><span>Sous-total (<?= count($books) ?> titre(s))</span><strong id="sumSub"><?= money($subtotal) ?></strong></div>
          <?php if ($discount > 0): ?><div class="summary-row"><span>Réduction</span><strong style="color:var(--success)">-<?= money($discount) ?></strong></div><?php endif; ?>
          <div class="summary-row total"><span>Total</span><strong id="sumTotal"><?= money($total) ?></strong></div>
          <a href="<?php h(url('checkout.php')); ?>" class="btn btn-block btn-lg" style="margin-top:16px">Commander <?= icon('arrow') ?></a>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:14px;color:var(--muted);font-size:.78rem"><?= icon('wallet') ?> Paiement BaridiMob sécurisé</div>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>
<script>
async function cartRemove(id){
  const r = await Biblio.removeFromCart(id);
  if(r.ok){ const row=document.querySelector('.cart-row[data-id="'+id+'"]'); row.style.opacity='0'; setTimeout(()=>{row.remove(); if(!document.querySelector('.cart-row')) location.reload();},250);
    document.getElementById('sumSub').textContent=r.subtotal; document.getElementById('sumTotal').textContent=r.total; }
}
async function applyCoupon(){
  const r = await Biblio.api('/api/coupon.php',{code:document.getElementById('couponInput').value.trim()});
  Biblio.toast(r.message, r.ok?'success':'error','Code promo'); if(r.ok) setTimeout(()=>location.reload(),700);
}
</script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
