<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$rip = setting('baridimob_rip', '007 9999 0001 2345 6789');
$pages = [
  'paiement' => ['Paiement BaridiMob', 'wallet', '<p>Le règlement se fait par <strong>BaridiMob</strong>, simple et sécurisé.</p><h2>Étapes</h2><ul><li>Ajoutez le livre au panier et validez la commande.</li><li>Effectuez un virement BaridiMob vers le RIP : <strong>' . e($rip) . '</strong>.</li><li>Indiquez la référence de la transaction (ou joignez une capture).</li><li>Notre équipe valide sous 24h : votre livre s\'ouvre dans votre bibliothèque.</li></ul><h2>Bientôt</h2><p>Paiement par carte Edahabia / CIB, l\'architecture est déjà prête.</p>'],
  'cgv' => ['Conditions générales', 'shield', '<p>En achetant sur La Bibliothèque Numérique, vous acceptez les présentes conditions.</p><h2>Produits numériques</h2><p>Les livres sont des produits numériques accessibles en lecture en ligne après paiement validé. Aucun envoi physique.</p><h2>Accès</h2><p>L\'accès est personnel et permanent une fois la commande validée.</p>'],
  'confidentialite' => ['Confidentialité', 'shield', '<p>Nous protégeons vos données personnelles.</p><h2>Usage</h2><p>Vos informations servent uniquement au traitement de vos commandes et à votre accès aux livres. Elles ne sont jamais revendues.</p>'],
];
$key = input('p'); $page = $pages[$key] ?? null;
if (!$page) { redirect('index.php'); }
$pageTitle = $page[0];
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => $page[0]]]) ?></div>
<section class="section-sm"><div class="container" style="max-width:760px">
  <div class="ic" style="width:64px;height:64px;border-radius:50%;background:var(--surface-2);display:grid;place-items:center;color:var(--coffee);margin-bottom:16px"><?= icon($page[1]) ?></div>
  <h1><?php h($page[0]); ?></h1>
  <div class="prose" style="font-size:1.05rem;line-height:1.8"><?= $page[2] ?></div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
