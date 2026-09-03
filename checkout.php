<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_once INCLUDES_PATH . '/payment.php';

require_login();
$u = current_user();

// Achat direct (?buy=id) ou panier
$buyId = (int) input('buy');
if ($buyId) {
    $one = book_by_id($buyId);
    $books = ($one && !has_book_access($buyId)) ? [$one] : [];
    $mode = 'buy';
} else {
    $books = array_filter(books_by_ids(array_keys(cart())), fn($b) => !has_book_access((int) $b['id']));
    $mode = 'cart';
}
if (empty($books)) { flash('Aucun titre à commander.', 'info'); redirect('catalogue.php'); }

$subtotal = 0;
foreach ($books as $b) { $subtotal += (float) $b['price']; }
$discount = $mode === 'cart' ? min((float) ($_SESSION['coupon']['amount'] ?? 0), $subtotal) : 0;
$total = max(0, $subtotal - $discount);

$errors = [];
if (is_post()) {
    csrf_check();
    $name = input('name'); $phone = input('phone'); $email = input('email');
    $payRef = mb_substr(input('payment_ref'), 0, 120);

    if (!$name) { $errors[] = 'Le nom est requis.'; }
    if (!preg_match('/^0[567][0-9]{8}$/', preg_replace('/\s/', '', $phone))) { $errors[] = 'Numéro de téléphone invalide (ex. 0561234567).'; }

    // Reçu (capture) facultatif
    $receiptFile = '';
    if (!empty($_FILES['receipt']['name']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $_FILES['receipt']['size'] < 4 * 1024 * 1024) {
            $receiptFile = 'reçu-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
            @move_uploaded_file($_FILES['receipt']['tmp_name'], UPLOADS_PATH . '/receipts/' . $receiptFile);
        }
    }

    if (!$errors) {
        $ref = 'CMD-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        // Statut : 'awaiting' si le client a fourni une preuve, sinon 'pending'.
        $status = ($payRef !== '' || $receiptFile !== '') ? 'awaiting' : 'pending';
        $orderId = Database::insert(
            'INSERT INTO orders (reference, customer_id, customer_name, email, phone, subtotal, discount, total, payment_method, payment_ref, receipt_file, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$ref, $u['id'], $name, $email, $phone, $subtotal, $discount, $total, 'baridimob', $payRef, $receiptFile, $status]
        );
        foreach ($books as $b) {
            Database::run('INSERT INTO order_items (order_id, book_id, title, price) VALUES (?,?,?,?)', [$orderId, $b['id'], $b['title'], $b['price']]);
        }
        if ($mode === 'cart') { unset($_SESSION['cart'], $_SESSION['coupon']); }
        redirect('order-confirmation.php?ref=' . urlencode($ref));
    }
}

$instr = BaridimobGateway::manualInstructions();
$pageTitle = 'Commander';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Panier', 'url' => url('cart.php')], ['label' => 'Commander']]) ?></div>
<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:6px">Finaliser ma commande</h1>
    <p style="color:var(--muted);margin-bottom:24px">Paiement par <strong>BaridiMob</strong>, votre accès est débloqué dès validation par notre équipe.</p>

    <?php if ($errors): ?><div class="card-panel" style="border-left:4px solid var(--danger);margin-bottom:20px"><?php foreach ($errors as $er): ?><div style="color:var(--danger)"><?= icon('close') ?> <?php h($er); ?></div><?php endforeach; ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="cart-layout">
      <?= csrf_field() ?>
      <div style="display:grid;gap:22px">
        <div class="card-panel">
          <h3 style="margin-bottom:16px">Vos coordonnées</h3>
          <div class="form-row">
            <div class="field"><label>Nom complet *</label><input name="name" required value="<?= e($u['first_name'].' '.$u['last_name']) ?>"></div>
            <div class="field"><label>Téléphone *</label><input name="phone" required placeholder="0561234567" value="<?= e($u['phone'] ?? '') ?>"></div>
          </div>
          <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= e($u['email']) ?>"></div>
        </div>

        <div class="pay-card">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
            <span class="pay-method-logo"><?= icon('wallet') ?> BaridiMob</span>
            <strong>Instructions de paiement</strong>
          </div>
          <p style="color:var(--muted);font-size:.92rem">Effectuez un virement <strong>BaridiMob</strong> du montant total vers le compte ci-dessous, puis indiquez la référence de la transaction. Vous pouvez aussi joindre une capture du reçu.</p>
          <div class="rip-box">
            <div><small style="color:var(--muted)">RIP / Compte</small><div class="rip" id="ripValue"><?php h($instr['rip']); ?></div><small style="color:var(--muted)">Bénéficiaire : <?php h($instr['name']); ?></small></div>
            <button type="button" class="btn btn-sm btn-ghost" onclick="navigator.clipboard.writeText('<?php h($instr['rip']); ?>');Biblio.toast('RIP copié','success')"><?= icon('copy') ?> Copier</button>
          </div>
          <div class="chip" style="margin:6px 0 16px;white-space:normal;line-height:1.5"><?= icon('chat') ?> <?php h($instr['note']); ?></div>
          <div class="form-row">
            <div class="field"><label>Référence de transaction BaridiMob</label><input name="payment_ref" placeholder="Ex. 123456789"></div>
            <div class="field"><label>Capture du reçu (facultatif)</label><input type="file" name="receipt" accept="image/*"></div>
          </div>
          <p style="color:var(--muted);font-size:.78rem"><?= icon('shield') ?> Vous pouvez aussi valider sans référence : votre commande restera « à payer » jusqu'à réception du virement.</p>
        </div>
      </div>

      <aside class="cart-summary">
        <h3 style="margin-bottom:14px">Votre commande</h3>
        <?php foreach ($books as $b): ?>
        <div class="summary-row"><span><?php h(excerpt($b['title'],28)); ?></span><strong><?= e(book_price_label($b)) ?></strong></div>
        <?php endforeach; ?>
        <hr class="divider" style="margin:12px 0">
        <div class="summary-row"><span>Sous-total</span><strong><?= money($subtotal) ?></strong></div>
        <?php if ($discount > 0): ?><div class="summary-row"><span>Réduction</span><strong style="color:var(--success)">-<?= money($discount) ?></strong></div><?php endif; ?>
        <div class="summary-row total"><span>Total</span><strong><?= money($total) ?></strong></div>
        <button class="btn btn-block btn-lg" style="margin-top:16px" type="submit">Valider ma commande</button>
        <p style="color:var(--muted);font-size:.78rem;margin-top:12px;text-align:center">Les titres sont numériques, aucune livraison. Lecture en ligne après validation.</p>
      </aside>
    </form>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
