<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_once INCLUDES_PATH . '/payment.php';
require_once INCLUDES_PATH . '/captcha.php';

require_login();
satim_ensure_schema();
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

$satimOn   = SatimGateway::enabled();
$canCard   = $satimOn && $total >= 50;   // montant minimum SATIM : 50 DA
$errors = [];

/** Crée la commande + les lignes, renvoie [orderId, reference]. */
function create_order(array $u, array $books, float $subtotal, float $discount, float $total, string $method, string $status, string $payRef = '', string $receiptFile = ''): array
{
    $ref = 'CMD-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $orderId = Database::insert(
        'INSERT INTO orders (reference, customer_id, customer_name, email, phone, subtotal, discount, total, payment_method, payment_ref, receipt_file, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [$ref, $u['id'], input('name'), input('email'), preg_replace('/\s/', '', input('phone')), $subtotal, $discount, $total, $method, $payRef, $receiptFile, $status]
    );
    foreach ($books as $b) {
        Database::run('INSERT INTO order_items (order_id, book_id, title, price) VALUES (?,?,?,?)', [$orderId, $b['id'], $b['title'], $b['price']]);
    }
    return [$orderId, $ref];
}

if (is_post()) {
    csrf_check();
    $action = input('action');
    $name = input('name'); $phone = preg_replace('/\s/', '', input('phone'));

    if (!$name) { $errors[] = 'Le nom est requis.'; }
    if (!preg_match('/^0[567][0-9]{8}$/', $phone)) { $errors[] = 'Numéro de téléphone invalide (ex. 0561234567).'; }

    /* ---------------- Paiement par carte CIB / Edahabia (SATIM) ---------- */
    if ($action === 'card' && $canCard) {
        if (!input('accept_cgv')) { $errors[] = 'Vous devez accepter les conditions générales de vente et de paiement.'; }
        if (!captcha_verify())    { $errors[] = 'Le code de sécurité (captcha) est incorrect. Merci de réessayer.'; }

        if (!$errors) {
            [$orderId, $ref] = create_order($u, $books, $subtotal, $discount, $total, 'satim', 'pending');
            $orderNumber = SatimGateway::newOrderNumber();
            Database::run('UPDATE orders SET order_number = ? WHERE id = ?', [$orderNumber, $orderId]);

            $returnUrl = SatimGateway::publicUrl('payment-return.php');
            $reg = SatimGateway::register(
                $orderNumber,
                SatimGateway::amountToMinor($total),
                $returnUrl,
                $returnUrl, // failUrl : même page, distinguée par le résultat de la confirmation
                'Commande ' . $ref . ' - ' . setting('site_name', 'La Bibliothèque Numerique')
            );

            if ($reg['ok']) {
                Database::run('UPDATE orders SET satim_order_id = ? WHERE id = ?', [$reg['orderId'], $orderId]);
                if ($mode === 'cart') { unset($_SESSION['cart'], $_SESSION['coupon']); }
                // Redirection vers la page de paiement SATIM (navigateur, hors iframe).
                header('Location: ' . $reg['formUrl']);
                exit;
            }
            Database::run("UPDATE orders SET status = 'cancelled', note = ? WHERE id = ?", ['Echec enregistrement SATIM: ' . $reg['error'], $orderId]);
            $errors[] = 'Le paiement n\'a pas pu être initié. ' . $reg['error'];
        }
    }

    /* ---------------- Paiement manuel BaridiMob (secondaire) ------------- */
    if ($action === 'baridimob') {
        $payRef = mb_substr(input('payment_ref'), 0, 120);
        $receiptFile = '';
        if (!empty($_FILES['receipt']['name']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $_FILES['receipt']['size'] < 4 * 1024 * 1024) {
                $receiptFile = 'recu-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                @move_uploaded_file($_FILES['receipt']['tmp_name'], UPLOADS_PATH . '/receipts/' . $receiptFile);
            }
        }
        if (!$errors) {
            $status = ($payRef !== '' || $receiptFile !== '') ? 'awaiting' : 'pending';
            [$orderId, $ref] = create_order($u, $books, $subtotal, $discount, $total, 'baridimob', $status, $payRef, $receiptFile);
            if ($mode === 'cart') { unset($_SESSION['cart'], $_SESSION['coupon']); }
            redirect('order-confirmation.php?ref=' . urlencode($ref));
        }
    }
}

$instr = BaridimobGateway::manualInstructions();
$pageTitle = 'Paiement';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Panier', 'url' => url('cart.php')], ['label' => 'Paiement']]) ?></div>
<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:6px">Finaliser et payer ma commande</h1>
    <p style="color:var(--muted);margin-bottom:24px">Paiement en ligne sécurisé par carte <strong>CIB</strong> / <strong>Edahabia</strong> via la plateforme <strong>SATIM</strong>.</p>

    <?php if ($errors): ?><div class="card-panel" style="border-left:4px solid var(--danger);margin-bottom:20px"><?php foreach ($errors as $er): ?><div style="color:var(--danger)"><?= icon('close') ?> <?php h($er); ?></div><?php endforeach; ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="cart-layout">
      <?= csrf_field() ?>
      <div style="display:grid;gap:22px">
        <div class="card-panel">
          <h3 style="margin-bottom:16px">Vos coordonnées</h3>
          <div class="form-row">
            <div class="field"><label>Nom complet *</label><input name="name" required value="<?= e(input('name') ?: $u['first_name'].' '.$u['last_name']) ?>"></div>
            <div class="field"><label>Téléphone *</label><input name="phone" required placeholder="0561234567" value="<?= e(input('phone') ?: ($u['phone'] ?? '')) ?>"></div>
          </div>
          <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= e(input('email') ?: $u['email']) ?>"></div>
        </div>

        <?php if ($canCard): ?>
        <!-- ================= Paiement carte CIB / Edahabia (SATIM) ================= -->
        <div class="pay-card pay-card-primary">
          <div class="pay-head">
            <span class="pay-brands">
              <img src="<?php h(asset('images/cib-edahabia.png')); ?>" alt="CIB / Edahabia" height="40">
            </span>
            <strong>Paiement par carte CIB / Edahabia</strong>
          </div>

          <!-- Montant total mis en évidence (exigence SATIM) -->
          <div class="pay-amount">
            <span class="pay-amount-label">Montant à payer</span>
            <span class="pay-amount-value"><?= number_format($total, 2, ',', ' ') ?> DZD</span>
          </div>

          <!-- Conditions générales + case d'acceptation (affichées avant paiement) -->
          <div class="pay-conditions">
            <strong>Conditions générales du paiement en ligne</strong>
            <ul>
              <li>Le paiement est traité de façon sécurisée par la plateforme <strong>SATIM</strong> ; aucune donnée de carte ne transite ni n'est stockée par notre site.</li>
              <li>Le montant débité correspond exactement au total affiché ci-dessus, en dinars algériens (DZD).</li>
              <li>Les articles sont des produits numériques : l'accès en lecture est débloqué immédiatement après acceptation du paiement, sans droit de rétractation une fois l'accès ouvert.</li>
              <li>En cas de problème de paiement, contactez le numéro vert SATIM <strong><?= e(SatimGateway::greenNumber()) ?></strong> (appel gratuit).</li>
            </ul>
            <label class="pay-accept">
              <input type="checkbox" name="accept_cgv" value="1" required>
              <span>J'ai lu et j'accepte les <a href="<?php h(url('page.php?p=cgv')); ?>" target="_blank" rel="noopener">conditions générales de vente</a> et les conditions du paiement en ligne.</span>
            </label>
          </div>

          <!-- Captcha anti-robot -->
          <?= captcha_field() ?>

          <button type="submit" name="action" value="card" class="btn btn-pay btn-block btn-lg">
            <img src="<?php h(asset('images/cib-edahabia.png')); ?>" alt="" height="26" style="border-radius:4px">
            Payer <?= number_format($total, 2, ',', ' ') ?> DZD par carte
          </button>
          <p class="pay-secure"><?= icon('lock') ?> Vous serez redirigé vers la page de paiement sécurisée SATIM (connexion SSL).</p>
        </div>
        <?php elseif ($satimOn && $total < 50): ?>
        <div class="card-panel" style="border-left:4px solid var(--gold)"><?= icon('chat') ?> Le montant minimum pour un paiement par carte est de 50 DA.</div>
        <?php endif; ?>

        <!-- ================= Paiement manuel BaridiMob (secondaire) ================= -->
        <details class="pay-card pay-card-alt" <?= $canCard ? '' : 'open' ?>>
          <summary><span class="pay-method-logo"><?= icon('wallet') ?> BaridiMob</span> Autre moyen : virement BaridiMob (validation manuelle)</summary>
          <div style="margin-top:14px">
            <p style="color:var(--muted);font-size:.92rem">Effectuez un virement <strong>BaridiMob</strong> du montant total vers le compte ci-dessous, puis indiquez la référence de la transaction.</p>
            <div class="rip-box">
              <div><small style="color:var(--muted)">RIP / Compte</small><div class="rip"><?php h($instr['rip']); ?></div><small style="color:var(--muted)">Bénéficiaire : <?php h($instr['name']); ?></small></div>
              <button type="button" class="btn btn-sm btn-ghost" onclick="navigator.clipboard.writeText('<?php h($instr['rip']); ?>');Biblio&&Biblio.toast&&Biblio.toast('RIP copié','success')"><?= icon('copy') ?> Copier</button>
            </div>
            <div class="form-row">
              <div class="field"><label>Référence de transaction BaridiMob</label><input name="payment_ref" placeholder="Ex. 123456789"></div>
              <div class="field"><label>Capture du reçu (facultatif)</label><input type="file" name="receipt" accept="image/*"></div>
            </div>
            <button type="submit" name="action" value="baridimob" formnovalidate class="btn btn-outline">Valider par BaridiMob</button>
          </div>
        </details>
      </div>

      <aside class="cart-summary">
        <h3 style="margin-bottom:14px">Votre commande</h3>
        <?php foreach ($books as $b): ?>
        <div class="summary-row"><span><?php h(excerpt($b['title'],28)); ?></span><strong><?= e(book_price_label($b)) ?></strong></div>
        <?php endforeach; ?>
        <hr class="divider" style="margin:12px 0">
        <div class="summary-row"><span>Sous-total</span><strong><?= money($subtotal) ?></strong></div>
        <?php if ($discount > 0): ?><div class="summary-row"><span>Réduction</span><strong style="color:var(--success)">-<?= money($discount) ?></strong></div><?php endif; ?>
        <div class="summary-row total"><span>Total à payer</span><strong><?= number_format($total, 2, ',', ' ') ?> DZD</strong></div>
        <p style="color:var(--muted);font-size:.78rem;margin-top:12px;text-align:center">Les titres sont numériques, aucune livraison. Lecture en ligne dès validation du paiement.</p>
        <div class="pay-trust"><img src="<?php h(asset('images/satim-3020.png')); ?>" alt="SATIM 3020 - appel gratuit" height="40"></div>
      </aside>
    </form>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
