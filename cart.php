<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$cart = cart();
$products = products_by_ids(array_keys($cart));
$subtotal = 0;
foreach ($products as $p) { $subtotal += $p['price'] * $cart[$p['id']]['qty']; }
$freeMin  = (float) setting('free_shipping_min', 8000);
$shipping = ($subtotal >= $freeMin || $subtotal == 0) ? 0 : (float) setting('shipping_flat', 600);
$discount = 0;
if (!empty($_SESSION['coupon'])) { $discount = (float) $_SESSION['coupon']['amount']; }
$total = max(0, $subtotal + $shipping - $discount);
$progress = $freeMin > 0 ? min(100, round($subtotal / $freeMin * 100)) : 100;

$pageTitle = 'Mon panier';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Panier']]) ?></div>

<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:24px">Mon panier</h1>

    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="ic"><?= icon('cart') ?></div>
        <h3>Votre panier est vide</h3>
        <p style="color:var(--muted)">Découvrez nos produits et ajoutez vos coups de cœur.</p>
        <a href="<?php h(url('shop.php')); ?>" class="btn btn-lg">Explorer la boutique <?= icon('arrow') ?></a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div>
          <!-- Barre livraison gratuite -->
          <div class="cart-summary" style="position:static;margin-bottom:20px;padding:18px 22px">
            <?php if ($shipping == 0 && $subtotal > 0): ?>
              <strong style="color:var(--success)"><?= icon('check') ?> Félicitations, la livraison est offerte !</strong>
            <?php else: ?>
              <strong id="freeMsg">Plus que <?= money($freeMin - $subtotal) ?> pour la livraison gratuite</strong>
            <?php endif; ?>
            <div class="ship-progress"><i id="freeBar" style="width:<?= $progress ?>%"></i></div>
          </div>

          <div class="cart-items" id="cartItems">
            <?php foreach ($products as $p): $qty = $cart[$p['id']]['qty']; ?>
            <div class="cart-row" data-id="<?= (int) $p['id'] ?>">
              <img src="<?php h(upload_url($p['primary_image'] ?? null, 'products', $p['name'])); ?>" alt="<?php h($p['name']); ?>">
              <div>
                <a href="<?php h(url('product.php?slug=' . $p['slug'])); ?>" class="ci-name"><?php h($p['name']); ?></a>
                <div class="ci-meta"><?php h($p['brand_name']); ?> · <?php h($p['volume']); ?></div>
                <div class="qty" style="margin-top:8px;width:fit-content">
                  <button type="button" onclick="cartQty(<?= (int)$p['id'] ?>,-1)"><?= icon('minus') ?></button>
                  <input type="number" value="<?= $qty ?>" readonly data-qty>
                  <button type="button" onclick="cartQty(<?= (int)$p['id'] ?>,1)"><?= icon('plus') ?></button>
                </div>
              </div>
              <div style="text-align:right">
                <div class="ci-price" data-line><?= money($p['price'] * $qty) ?></div>
                <button class="cart-remove" onclick="cartRemove(<?= (int)$p['id'] ?>)"><?= icon('trash') ?> Retirer</button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:18px"><a href="<?php h(url('shop.php')); ?>" class="text-rose" style="font-weight:600"><?= icon('arrow','flip') ?> Continuer mes achats</a></div>
        </div>

        <!-- Récapitulatif -->
        <aside class="cart-summary">
          <h3 style="margin-bottom:16px">Récapitulatif</h3>
          <div class="coupon-row">
            <input type="text" id="couponInput" placeholder="Code promo" value="<?= e($_SESSION['coupon']['code'] ?? '') ?>">
            <button class="btn btn-sm" onclick="applyCoupon()">Appliquer</button>
          </div>
          <div class="summary-row"><span>Sous-total</span><strong id="sumSubtotal"><?= money($subtotal) ?></strong></div>
          <div class="summary-row"><span>Livraison</span><strong id="sumShipping"><?= $shipping == 0 ? 'Offerte' : money($shipping) ?></strong></div>
          <?php if ($discount > 0): ?><div class="summary-row" id="sumDiscountRow"><span>Réduction</span><strong style="color:var(--success)">-<?= money($discount) ?></strong></div><?php endif; ?>
          <div class="summary-row total"><span>Total</span><strong id="sumTotal"><?= money($total) ?></strong></div>
          <a href="<?php h(url('checkout.php')); ?>" class="btn btn-block btn-lg" style="margin-top:16px">Passer la commande <?= icon('arrow') ?></a>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;color:var(--muted);font-size:.78rem"><?= icon('shield') ?> Paiement 100% sécurisé</div>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
async function cartQty(id, d){
  const row = document.querySelector('.cart-row[data-id="'+id+'"]');
  const input = row.querySelector('[data-qty]');
  const qty = Math.max(1, parseInt(input.value)+d);
  const r = await Bella.updateCartQty(id, qty);
  if(r.ok){ input.value = qty; refreshSummary(r); if(r.lines[id]) row.querySelector('[data-line]').textContent = r.lines[id].lineTotal; }
}
async function cartRemove(id){
  const r = await Bella.removeFromCart(id);
  if(r.ok){ const row=document.querySelector('.cart-row[data-id="'+id+'"]'); row.style.opacity='0'; setTimeout(()=>{row.remove(); if(!document.querySelector('.cart-row')) location.reload();},250); refreshSummary(r); }
}
function refreshSummary(r){
  document.getElementById('sumSubtotal').textContent = r.subtotal;
  document.getElementById('sumShipping').textContent = r.shipping;
  document.getElementById('sumTotal').textContent = r.total;
  const bar=document.getElementById('freeBar'); if(bar) bar.style.width = r.freeProgress+'%';
  const msg=document.getElementById('freeMsg');
  if(msg){ if(r.shippingRaw===0){ msg.textContent='Livraison offerte !'; msg.style.color='var(--success)'; }
    else msg.textContent='Plus que '+ (r.freeMin - r.subtotalRaw).toLocaleString('fr')+' '+window.BELLA.currency+' pour la livraison gratuite'; }
}
async function applyCoupon(){
  const code = document.getElementById('couponInput').value.trim();
  const r = await Bella.api('/api/coupon.php', { code });
  Bella.toast(r.message, r.ok?'success':'error', 'Code promo');
  if(r.ok) setTimeout(()=>location.reload(), 700);
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
