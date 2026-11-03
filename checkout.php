<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$cart = cart();
$products = products_by_ids(array_keys($cart));
if (empty($products)) { redirect('cart.php'); }

$subtotal = 0;
foreach ($products as $p) { $subtotal += $p['price'] * $cart[$p['id']]['qty']; }
$freeMin  = (float) setting('free_shipping_min', 8000);
$shipping = $subtotal >= $freeMin ? 0 : (float) setting('shipping_flat', 600);
$discount = (float) ($_SESSION['coupon']['amount'] ?? 0);
$total = max(0, $subtotal + $shipping - $discount);

$wilayas = ['Adrar','Chlef','Laghouat','Oum El Bouaghi','Batna','Béjaïa','Biskra','Béchar','Blida','Bouira','Tamanrasset','Tébessa','Tlemcen','Tiaret','Tizi Ouzou','Alger','Djelfa','Jijel','Sétif','Saïda','Skikda','Sidi Bel Abbès','Annaba','Guelma','Constantine','Médéa','Mostaganem','M\'Sila','Mascara','Ouargla','Oran','El Bayadh','Illizi','Bordj Bou Arréridj','Boumerdès','El Tarf','Tindouf','Tissemsilt','El Oued','Khenchela','Souk Ahras','Tipaza','Mila','Aïn Defla','Naâma','Aïn Témouchent','Ghardaïa','Relizane'];

$errors = [];
if (is_post()) {
    csrf_check();
    $name   = input('name');   $phone = input('phone');
    $email  = input('email');  $addr  = input('address');
    $wilaya = input('wilaya'); $pay   = input('payment_method', 'cod');
    $note   = mb_substr(input('note'), 0, 500);

    if (!$name)   { $errors[] = 'Le nom est requis.'; }
    if (!preg_match('/^0[567][0-9]{8}$/', preg_replace('/\s/', '', $phone))) { $errors[] = 'Numéro de téléphone invalide (ex. 0561234567).'; }
    if (!$addr)   { $errors[] = 'L\'adresse est requise.'; }
    if (!$wilaya) { $errors[] = 'Veuillez choisir une wilaya.'; }

    if (!$errors) {
        $ref = 'CMD-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $orderId = Database::insert(
            'INSERT INTO orders (reference, customer_id, customer_name, email, phone, address, wilaya, subtotal, shipping, discount, total, payment_method, status, note)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$ref, current_user()['id'] ?? null, $name, $email, $phone, $addr, $wilaya, $subtotal, $shipping, $discount, $total, $pay, 'pending', $note]
        );
        foreach ($products as $p) {
            Database::run('INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?,?,?,?,?)',
                [$orderId, $p['id'], $p['name'], $p['price'], $cart[$p['id']]['qty']]);
            Database::run('UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?', [$cart[$p['id']]['qty'], $p['id']]);
        }
        // Fidélité : 1 point / 100 DA
        if ($u = current_user()) {
            Database::run('UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?', [(int) floor($total / 100), $u['id']]);
        }
        unset($_SESSION['cart'], $_SESSION['coupon']);
        $_SESSION['last_order'] = $ref;
        redirect('order-confirmation.php?ref=' . urlencode($ref));
    }
}

$u = current_user();
$pageTitle = 'Commande';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Panier', 'url' => url('cart.php')], ['label' => 'Commande']]) ?></div>

<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:24px">Finaliser ma commande</h1>

    <?php if ($errors): ?>
      <div class="card-panel" style="border-left:4px solid var(--danger);margin-bottom:20px">
        <?php foreach ($errors as $er): ?><div style="color:var(--danger)"><?= icon('close') ?> <?php h($er); ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="cart-layout">
      <?= csrf_field() ?>
      <div class="card-panel">
        <h3 style="margin-bottom:18px">Informations de livraison</h3>
        <div class="form-row">
          <div class="field"><label>Nom complet *</label><input name="name" required value="<?= e($u ? $u['first_name'].' '.$u['last_name'] : input('name')) ?>"></div>
          <div class="field"><label>Téléphone *</label><input name="phone" required placeholder="0561234567" value="<?= e($u['phone'] ?? input('phone')) ?>"></div>
        </div>
        <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= e($u['email'] ?? input('email')) ?>"></div>
        <div class="form-row">
          <div class="field"><label>Wilaya *</label>
            <select name="wilaya" class="select" style="width:100%" required>
              <option value="">Choisir…</option>
              <?php foreach ($wilayas as $w): ?><option <?= input('wilaya')===$w?'selected':'' ?>><?php h($w); ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Adresse complète *</label><input name="address" required placeholder="Rue, quartier, ville" value="<?= e(input('address')) ?>"></div>
        </div>
        <div class="field"><label>Note (facultatif)</label><textarea name="note" placeholder="Instructions de livraison…"><?= e(input('note')) ?></textarea></div>

        <h3 style="margin:24px 0 14px">Mode de paiement</h3>
        <div style="display:grid;gap:10px">
          <?php
          $pays = [
            'cod' => ['Paiement à la livraison', 'Payez en espèces à réception de votre commande', 'wallet'],
            'baridimob' => ['BaridiMob', 'Paiement mobile Algérie Poste', 'phone'],
            'ccp' => ['Versement CCP / CIB', 'Par virement bancaire', 'box'],
          ];
          $first = true;
          foreach ($pays as $key => $pm): ?>
          <label class="pd-feature" style="cursor:pointer;border:1.5px solid var(--border)">
            <input type="radio" name="payment_method" value="<?= $key ?>" <?= $first ? 'checked' : '' ?> style="accent-color:var(--rose)">
            <?= icon($pm[2]) ?>
            <div><strong><?php h($pm[0]); ?></strong><br><span style="color:var(--muted);font-size:.82rem"><?php h($pm[1]); ?></span></div>
          </label>
          <?php $first = false; endforeach; ?>
          <p style="color:var(--muted);font-size:.8rem"><?= icon('shield') ?> Les paiements en ligne (Edahabia, Visa, Mastercard, PayPal) seront bientôt disponibles.</p>
        </div>
      </div>

      <aside class="cart-summary">
        <h3 style="margin-bottom:14px">Votre commande</h3>
        <?php foreach ($products as $p): ?>
        <div class="summary-row"><span><?php h(excerpt($p['name'],26)); ?> ×<?= $cart[$p['id']]['qty'] ?></span><strong><?= money($p['price']*$cart[$p['id']]['qty']) ?></strong></div>
        <?php endforeach; ?>
        <hr class="divider" style="margin:12px 0">
        <div class="summary-row"><span>Sous-total</span><strong><?= money($subtotal) ?></strong></div>
        <div class="summary-row"><span>Livraison</span><strong><?= $shipping==0?'Offerte':money($shipping) ?></strong></div>
        <?php if ($discount>0): ?><div class="summary-row"><span>Réduction</span><strong style="color:var(--success)">-<?= money($discount) ?></strong></div><?php endif; ?>
        <div class="summary-row total"><span>Total</span><strong><?= money($total) ?></strong></div>
        <button class="btn btn-block btn-lg" style="margin-top:16px" type="submit">Confirmer la commande</button>
      </aside>
    </form>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
