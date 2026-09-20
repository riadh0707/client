<?php
/**
 * payment-return.php - Page de retour SATIM (returnUrl et failUrl).
 *
 * SATIM redirige le client ici avec ?orderId=<mdOrder>. On confirme la
 * transaction via acknowledgeTransaction.do puis on affiche le résultat :
 *   · Paiement accepté  -> tous les champs exigés + reçu (impression / PDF / e-mail)
 *   · Paiement rejeté   -> message trilingue + numéro vert SATIM
 *   · Autre / erreur     -> respCode_desc, sinon actionCodeDescription
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_once INCLUDES_PATH . '/payment.php';
satim_ensure_schema();

$mdOrder = trim((string) (input('orderId') ?: input('mdOrder')));
$order = $mdOrder ? Database::first('SELECT * FROM orders WHERE satim_order_id = ?', [$mdOrder]) : null;
if (!$order) { http_response_code(404); $mdOrder = ''; }

$green = SatimGateway::greenNumber();
$result = 'error';
$message = 'Transaction introuvable.';
$confirm = [];

if ($order) {
    // Déjà traitée (rechargement de la page) : on n'interroge pas SATIM à nouveau.
    if ($order['status'] === 'paid') {
        $result = 'accepted';
        $message = 'Votre paiement a été accepté';
    } else {
        $confirm = SatimGateway::confirm($mdOrder);
        $verdict = SatimGateway::classify($confirm);
        $result = $verdict['result'];
        $message = $verdict['message'];

        if ($result === 'accepted') {
            $pan   = (string) ($confirm['Pan'] ?? '');
            $appr  = (string) ($confirm['approvalCode'] ?? $confirm['authorizationResponseId'] ?? '');
            $resp  = (string) ($confirm['params']['respCode'] ?? '00');
            $brand = SatimGateway::cardBrand($pan);
            // Enregistre les détails SATIM, PUIS mark_order_paid() (qui passe le
            // statut à 'paid' et débloque l'accès). L'ordre est important :
            // mark_order_paid() ne fait rien si le statut est déjà 'paid'.
            Database::run(
                "UPDATE orders SET approval_code=?, resp_code=?, pan=?, card_brand=?, paid_at=? WHERE id=?",
                [$appr, $resp, $pan, $brand, date('Y-m-d H:i:s'), $order['id']]
            );
            mark_order_paid((int) $order['id']);
            $order = Database::first('SELECT * FROM orders WHERE id = ?', [$order['id']]);
        } else {
            // Rejet / échec : on garde une trace sans débloquer l'accès.
            $resp = (string) ($confirm['params']['respCode'] ?? '');
            Database::run("UPDATE orders SET status='cancelled', resp_code=?, note=? WHERE id=? AND status<>'paid'",
                [$resp, mb_substr($message, 0, 240), $order['id']]);
        }
    }
}

$pageTitle = $result === 'accepted' ? 'Paiement accepté' : 'Résultat du paiement';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm"><div class="container" style="max-width:720px">

<?php if ($result === 'accepted'): ?>
  <?php
    $when = $order['paid_at'] ?: date('Y-m-d H:i:s');
    $brand = $order['card_brand'] ?: 'CIB / Edahabia';
  ?>
  <div class="pay-result pay-ok">
    <div class="pay-result-ic"><?= icon('check') ?></div>
    <h1>Paiement accepté</h1>
    <p class="pay-result-msg"><?php h($message); ?></p>
  </div>

  <div class="card-panel receipt" id="receipt">
    <div class="receipt-head">
      <div>
        <strong style="font-size:1.1rem"><?php h(setting('site_name', 'La Bibliothèque Numérique')); ?></strong><br>
        <span style="color:var(--muted);font-size:.85rem">Reçu de paiement</span>
      </div>
      <span class="pay-brands"><img src="<?php h(asset('images/cib.svg')); ?>" alt="CIB" height="34"><img src="<?php h(asset('images/edahabia.svg')); ?>" alt="Edahabia" height="34"></span>
    </div>
    <table class="receipt-table">
      <tr><td>Message</td><td><?php h($message); ?></td></tr>
      <tr><td>Identifiant transaction (SATIM)</td><td><code><?php h($order['satim_order_id']); ?></code></td></tr>
      <tr><td>Numéro de commande</td><td><code><?php h($order['order_number'] ?: $order['reference']); ?></code></td></tr>
      <tr><td>Numéro d'autorisation</td><td><code><?php h($order['approval_code'] ?: '—'); ?></code></td></tr>
      <tr><td>Date et heure</td><td><?php h(date('d/m/Y H:i:s', strtotime($when))); ?></td></tr>
      <tr><td>Montant</td><td><strong><?= number_format((float) $order['total'], 2, ',', ' ') ?> DZD</strong></td></tr>
      <tr><td>Mode de paiement</td><td><?php h($brand); ?></td></tr>
      <?php if ($order['pan']): ?><tr><td>Carte</td><td><?php h($order['pan']); ?></td></tr><?php endif; ?>
    </table>
    <div class="receipt-foot">
      <img src="<?php h(asset('images/satim-3020.svg')); ?>" alt="SATIM 3020 appel gratuit" height="38">
      <span style="color:var(--muted);font-size:.82rem">En cas de problème de paiement, contactez le numéro vert SATIM <strong><?= e($green) ?></strong> (appel gratuit).</span>
    </div>
  </div>

  <div class="pay-actions no-print">
    <a href="<?php h(url('receipt.php?ref=' . urlencode($order['reference']))); ?>" class="btn btn-outline" target="_blank"><?= icon('receipt') ?> Voir le reçu</a>
    <button type="button" class="btn btn-outline" onclick="window.print()"><?= icon('pages') ?> Imprimer</button>
    <a href="<?php h(url('receipt.php?ref=' . urlencode($order['reference']) . '&format=pdf')); ?>" class="btn btn-outline"><?= icon('download') ?> Télécharger le PDF</a>
    <a href="<?php h(url('receipt.php?ref=' . urlencode($order['reference']) . '#email')); ?>" class="btn btn-outline"><?= icon('mail') ?> Envoyer par e-mail</a>
  </div>
  <div class="center" style="margin-top:22px">
    <a href="<?php h(url('customer/library.php')); ?>" class="btn"><?= icon('library') ?> Accéder à ma bibliothèque</a>
  </div>

<?php elseif ($result === 'rejected' || $result === 'declined'): ?>
  <div class="pay-result pay-ko">
    <div class="pay-result-ic"><?= icon('close') ?></div>
    <h1>Paiement refusé</h1>
    <p class="pay-result-msg"><?php h($message); ?></p>
  </div>
  <div class="card-panel center" style="margin-top:8px">
    <?php if ($order): ?><p style="color:var(--muted)">Commande <strong><?php h($order['order_number'] ?: $order['reference']); ?></strong> — aucun montant n'a été débité.</p><?php endif; ?>
    <div class="receipt-foot" style="justify-content:center;margin-top:14px">
      <img src="<?php h(asset('images/satim-3020.svg')); ?>" alt="SATIM 3020 appel gratuit" height="40">
      <span style="color:var(--muted);font-size:.85rem">En cas de problème de paiement, contactez le numéro vert SATIM <strong><?= e($green) ?></strong> (appel gratuit).</span>
    </div>
    <div style="margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="<?php h(url('cart.php')); ?>" class="btn btn-outline"><?= icon('cart') ?> Retour au panier</a>
      <a href="<?php h(url('catalogue.php')); ?>" class="btn">Continuer</a>
    </div>
  </div>

<?php else: ?>
  <div class="pay-result pay-ko">
    <div class="pay-result-ic"><?= icon('close') ?></div>
    <h1>Paiement non abouti</h1>
    <p class="pay-result-msg"><?php h($message); ?></p>
  </div>
  <div class="card-panel center" style="margin-top:8px">
    <div class="receipt-foot" style="justify-content:center">
      <img src="<?php h(asset('images/satim-3020.svg')); ?>" alt="SATIM 3020 appel gratuit" height="40">
      <span style="color:var(--muted);font-size:.85rem">En cas de problème de paiement, contactez le numéro vert SATIM <strong><?= e($green) ?></strong> (appel gratuit).</span>
    </div>
    <div style="margin-top:20px"><a href="<?php h(url('cart.php')); ?>" class="btn">Réessayer</a></div>
  </div>
<?php endif; ?>

</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
