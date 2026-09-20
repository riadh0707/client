<?php
/**
 * _devsatim-form.php - Page de paiement SIMULÉE (mode satim.mock uniquement).
 *
 * Reproduit la page de paiement hébergée par SATIM pour les tests hors-ligne :
 * on choisit une carte de test, puis on est redirigé vers returnUrl / failUrl
 * avec ?orderId=, exactement comme le ferait SATIM. Renvoie 404 en production.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/payment.php';

if (!SatimGateway::isMock()) { http_response_code(404); exit('Not found'); }
require_once INCLUDES_PATH . '/satim_mock.php';

$mdOrder = (string) input('mdOrder');
$store = satim_mock_load();
$rec = $store[$mdOrder] ?? null;
if (!$rec) { http_response_code(404); exit('Commande simulée introuvable.'); }

if (is_post()) {
    $pan = preg_replace('/\D/', '', (string) ($_POST['pan'] ?? ''));
    $applied = satim_mock_apply_card($mdOrder, $pan);
    $back = ($applied && $applied['status'] === 'accepted') ? $rec['returnUrl'] : $rec['failUrl'];
    $sep = str_contains($back, '?') ? '&' : '?';
    header('Location: ' . $back . $sep . 'orderId=' . urlencode($mdOrder));
    exit;
}

$amountDa = number_format($rec['amount'] / 100, 2, ',', ' ');
$cards = satim_mock_cards();
?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paiement SATIM (simulateur de test)</title>
<style>
  body{font-family:system-ui,Arial,sans-serif;background:#eef1f5;margin:0;padding:24px;color:#1f2733}
  .card{max-width:460px;margin:20px auto;background:#fff;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.12);overflow:hidden}
  .head{background:#0a3d62;color:#fff;padding:18px 22px;display:flex;align-items:center;justify-content:space-between}
  .head b{font-size:1.1rem}
  .badge{background:#f1c40f;color:#0a3d62;font-weight:700;border-radius:6px;padding:2px 8px;font-size:.7rem}
  .body{padding:22px}
  .amt{font-size:1.9rem;font-weight:800;color:#0a3d62;text-align:center;margin:6px 0 18px}
  label{display:block;font-size:.85rem;font-weight:600;margin:14px 0 6px}
  select,input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #cbd3dd;border-radius:8px;font-size:1rem}
  .row{display:flex;gap:12px}.row>div{flex:1}
  .pay{width:100%;margin-top:20px;background:#0a3d62;color:#fff;border:0;border-radius:8px;padding:14px;font-size:1.05rem;font-weight:700;cursor:pointer}
  .note{background:#fff6d8;border:1px solid #f0d98a;border-radius:8px;padding:10px 12px;font-size:.8rem;margin-top:16px;color:#7a5b1e}
  .warn{text-align:center;color:#a33;font-size:.75rem;margin-top:10px}
</style>
</head><body>
<div class="card">
  <div class="head"><b>Paiement CIB / Edahabia</b><span class="badge">SIMULATEUR</span></div>
  <form method="post" class="body">
    <div style="text-align:center;color:#5b6674;font-size:.85rem">Montant à payer</div>
    <div class="amt"><?= e($amountDa) ?> DZD</div>

    <label>Carte de test</label>
    <select name="pan" required>
      <?php foreach ($cards as $pan => $c): ?>
        <option value="<?= e($pan) ?>"><?= e($pan) ?> — <?= e($c['result'] === 'accepted' ? 'VALIDE ✔' : $c['desc']) ?></option>
      <?php endforeach; ?>
    </select>

    <div class="row">
      <div><label>Expiration</label><input value="01/2027" readonly></div>
      <div><label>CVV2</label><input value="***" readonly></div>
    </div>

    <button class="pay" type="submit">Payer <?= e($amountDa) ?> DZD</button>
    <div class="note"><strong>Environnement de simulation.</strong> Choisissez une carte de test pour reproduire chaque résultat (accepté / refusé). En production, cette page est celle, sécurisée, hébergée par la SATIM.</div>
    <div class="warn">Commande : <?= e($rec['orderNumber']) ?></div>
  </form>
</div>
</body></html>
