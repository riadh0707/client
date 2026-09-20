<?php
/**
 * receipt.php - Reçu de paiement d'une commande payée par carte (SATIM).
 *   ?ref=<reference>              -> reçu HTML (imprimable) + envoi par e-mail
 *   ?ref=<reference>&format=pdf   -> téléchargement du reçu en PDF (FPDF)
 *   POST send=1&to=<email>        -> envoi du reçu PDF par e-mail
 *
 * Réservé au propriétaire de la commande (ou à un administrateur).
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';
require_once INCLUDES_PATH . '/payment.php';
satim_ensure_schema();

$order = Database::first('SELECT * FROM orders WHERE reference = ?', [input('ref')]);
if (!$order) { http_response_code(404); exit('Reçu introuvable.'); }

// Contrôle d'accès : propriétaire connecté ou administrateur.
$u = current_user();
$isOwner = $u && (int) $u['id'] === (int) $order['customer_id'];
$isAdmin = function_exists('current_admin') && current_admin();
if (!$isOwner && !$isAdmin) { redirect('customer/login.php'); }
if ($order['status'] !== 'paid') { http_response_code(403); exit('Aucun reçu : cette commande n\'est pas payée.'); }

$items = Database::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
$green = SatimGateway::greenNumber();
$siteName = setting('site_name', 'La Bibliothèque Numérique');
$when = $order['paid_at'] ?: $order['created_at'];

/** Champs du reçu (libellé => valeur), réutilisés en HTML et en PDF. */
function receipt_rows(array $order, string $when): array
{
    return [
        'Message'                        => 'Votre paiement a été accepté',
        'Identifiant transaction (SATIM)'=> (string) $order['satim_order_id'],
        'Numéro de commande'             => (string) ($order['order_number'] ?: $order['reference']),
        "Numéro d'autorisation"          => (string) ($order['approval_code'] ?: '-'),
        'Date et heure'                  => date('d/m/Y H:i:s', strtotime($when)),
        'Montant'                        => number_format((float) $order['total'], 2, ',', ' ') . ' DZD',
        'Mode de paiement'               => (string) ($order['card_brand'] ?: 'CIB / Edahabia'),
        'Carte'                          => (string) ($order['pan'] ?: ''),
    ];
}

/* ------------------------------------------------------------------ PDF --- */
function receipt_pdf(array $order, array $items, string $siteName, string $green, string $when): string
{
    require_once ROOT_PATH . '/lib/vendor/autoload.php';
    $tr = fn($s) => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $s);
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFillColor(74, 50, 32);
    $pdf->Rect(0, 0, 210, 30, 'F');
    $pdf->SetTextColor(248, 242, 228);
    $pdf->SetFont('Helvetica', 'B', 16); $pdf->SetXY(15, 9);
    $pdf->Cell(0, 8, $tr($siteName), 0, 2);
    $pdf->SetFont('Helvetica', '', 11); $pdf->Cell(0, 6, $tr('Recu de paiement'), 0, 0);

    $pdf->SetTextColor(40, 30, 20); $pdf->SetY(40);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell(0, 8, $tr('Paiement accepte'), 0, 1); $pdf->Ln(2);

    $pdf->SetFont('Helvetica', '', 11);
    foreach (receipt_rows($order, $when) as $label => $value) {
        if ($value === '') { continue; }
        $pdf->SetFont('Helvetica', '', 10); $pdf->SetTextColor(120, 106, 82);
        $pdf->Cell(70, 8, $tr($label), 0, 0);
        $pdf->SetFont('Helvetica', 'B', 10); $pdf->SetTextColor(40, 30, 20);
        $pdf->MultiCell(110, 8, $tr($value), 0, 'L');
    }
    $pdf->Ln(4);
    $pdf->SetDrawColor(216, 201, 172); $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(4);
    $pdf->SetFont('Helvetica', 'B', 10); $pdf->SetTextColor(40, 30, 20);
    $pdf->Cell(0, 7, $tr('Detail de la commande'), 0, 1);
    $pdf->SetFont('Helvetica', '', 10); $pdf->SetTextColor(60, 50, 40);
    foreach ($items as $it) {
        $pdf->Cell(150, 7, $tr($it['title']), 0, 0);
        $pdf->Cell(30, 7, $tr(number_format((float) $it['price'], 2, ',', ' ') . ' DZD'), 0, 1, 'R');
    }
    $pdf->Ln(8);
    $pdf->SetTextColor(120, 106, 82); $pdf->SetFont('Helvetica', '', 9);
    $pdf->MultiCell(0, 5, $tr("Mode de paiement : carte CIB / Edahabia via SATIM. En cas de probleme de paiement, contactez le numero vert SATIM " . $green . " (appel gratuit)."), 0, 'L');
    return $pdf->Output('S');
}

if (input('format') === 'pdf') {
    $pdf = receipt_pdf($order, $items, $siteName, $green, $when);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="recu-' . $order['reference'] . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf; exit;
}

/* ---------------------------------------------------------------- E-mail --- */
$sent = null; $sendError = '';
if (is_post() && input('send')) {
    csrf_check();
    $to = trim((string) input('to'));
    if (!valid_email($to)) {
        $sendError = 'Adresse e-mail invalide.';
    } else {
        $pdf = receipt_pdf($order, $items, $siteName, $green, $when);
        $sent = receipt_send_email($to, $order, $siteName, $pdf);
        if (!$sent) { $sendError = "L'envoi a échoué (serveur de messagerie indisponible). Vous pouvez télécharger le PDF."; }
    }
}

/** Envoi du reçu PDF en pièce jointe via mail(). */
function receipt_send_email(string $to, array $order, string $siteName, string $pdf): bool
{
    $cfg = $GLOBALS['config']['mail'] ?? [];
    $from = $cfg['from_email'] ?? ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName = $cfg['from_name'] ?? $siteName;
    $boundary = 'bnd_' . bin2hex(random_bytes(8));
    $subject = '=?UTF-8?B?' . base64_encode('Reçu de paiement - ' . $order['reference']) . '?=';

    $headers  = 'From: ' . $fromName . ' <' . $from . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n";

    $body  = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= "Bonjour,\r\n\r\nVeuillez trouver ci-joint le reçu de votre paiement (commande " . $order['reference'] . ").\r\n\r\n" . $siteName . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: application/pdf; name=\"recu-" . $order['reference'] . ".pdf\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"recu-" . $order['reference'] . ".pdf\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdf)) . "\r\n";
    $body .= '--' . $boundary . "--";

    if (empty($cfg['enabled'])) { return false; } // pas de serveur mail configuré
    return @mail($to, $subject, $body, $headers);
}

$pageTitle = 'Reçu de paiement';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm"><div class="container" style="max-width:720px">
  <?php if ($sent): ?><div class="card-panel" style="border-left:4px solid var(--success);margin-bottom:16px" class="no-print"><?= icon('check') ?> Reçu envoyé à <strong><?php h(input('to')); ?></strong>.</div><?php endif; ?>
  <?php if ($sendError): ?><div class="card-panel" style="border-left:4px solid var(--danger);margin-bottom:16px"><?= icon('close') ?> <?php h($sendError); ?></div><?php endif; ?>

  <div class="card-panel receipt" id="receipt">
    <div class="receipt-head">
      <div><strong style="font-size:1.15rem"><?php h($siteName); ?></strong><br><span style="color:var(--muted);font-size:.85rem">Reçu de paiement</span></div>
      <span class="pay-brands"><img src="<?php h(asset('images/cib.svg')); ?>" alt="CIB" height="34"><img src="<?php h(asset('images/edahabia.svg')); ?>" alt="Edahabia" height="34"></span>
    </div>
    <table class="receipt-table">
      <?php foreach (receipt_rows($order, $when) as $label => $value): if ($value === '') continue; ?>
        <tr><td><?php h($label); ?></td><td><?php h($value); ?></td></tr>
      <?php endforeach; ?>
    </table>
    <div class="receipt-items">
      <?php foreach ($items as $it): ?><div class="summary-row"><span><?php h($it['title']); ?></span><strong><?= number_format((float) $it['price'], 2, ',', ' ') ?> DZD</strong></div><?php endforeach; ?>
    </div>
    <div class="receipt-foot">
      <img src="<?php h(asset('images/satim-3020.svg')); ?>" alt="SATIM 3020 appel gratuit" height="38">
      <span style="color:var(--muted);font-size:.82rem">En cas de problème de paiement, contactez le numéro vert SATIM <strong><?= e($green) ?></strong> (appel gratuit).</span>
    </div>
  </div>

  <div class="pay-actions no-print">
    <button type="button" class="btn btn-outline" onclick="window.print()"><?= icon('pages') ?> Imprimer</button>
    <a href="<?php h(url('receipt.php?ref=' . urlencode($order['reference']) . '&format=pdf')); ?>" class="btn btn-outline"><?= icon('download') ?> Télécharger le PDF</a>
  </div>

  <form method="post" class="card-panel no-print" id="email" style="margin-top:16px">
    <?= csrf_field() ?><input type="hidden" name="send" value="1">
    <h3 style="margin-bottom:12px"><?= icon('mail') ?> Envoyer le reçu par e-mail</h3>
    <div class="form-row">
      <div class="field" style="flex:1"><label>Adresse e-mail du destinataire</label><input type="email" name="to" required value="<?= e($order['email'] ?: ($u['email'] ?? '')) ?>"></div>
      <div class="field" style="display:flex;align-items:flex-end"><button class="btn">Envoyer</button></div>
    </div>
    <p style="color:var(--muted);font-size:.8rem">Le reçu est envoyé en pièce jointe au format PDF.</p>
  </form>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
