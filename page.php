<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';

$pages = [
    'livraison' => ['Livraison', 'truck', '<p>Nous livrons dans les <strong>58 wilayas d\'Algérie</strong>, à domicile ou en point de retrait.</p><h2>Délais</h2><p>Comptez 2 à 5 jours ouvrés selon votre région. Un appel de confirmation précède chaque expédition.</p><h2>Frais</h2><p>Livraison à ' . money(setting('shipping_flat', 600)) . ', <strong>offerte dès ' . money(setting('free_shipping_min', 8000)) . '</strong> d\'achat.</p><h2>Suivi</h2><p>Suivez votre commande en temps réel depuis votre espace client.</p>'],
    'paiement'  => ['Paiement', 'wallet', '<p>Plusieurs moyens de paiement sûrs et adaptés à l\'Algérie.</p><h2>Paiement à la livraison</h2><p>Réglez en espèces à réception, après vérification de votre colis.</p><h2>BaridiMob & CCP</h2><p>Payez par mobile ou par versement CCP/CIB.</p><h2>Bientôt disponible</h2><p>Paiement par carte Edahabia, CIB, Visa, Mastercard et PayPal.</p>'],
    'cgv'       => ['Conditions générales de vente', 'shield', '<p>Les présentes conditions régissent les ventes réalisées sur Bella Rosa. En passant commande, vous les acceptez sans réserve.</p><h2>Produits</h2><p>Nos produits sont décrits avec la plus grande exactitude. Les visuels sont non contractuels.</p><h2>Prix</h2><p>Les prix sont indiqués en dinars algériens, toutes taxes comprises.</p><h2>Rétractation</h2><p>Vous disposez de 14 jours pour retourner un produit non ouvert.</p>'],
    'confidentialite' => ['Politique de confidentialité', 'shield', '<p>Votre vie privée est essentielle. Nous protégeons vos données personnelles.</p><h2>Données collectées</h2><p>Nous collectons uniquement les informations nécessaires au traitement de vos commandes.</p><h2>Utilisation</h2><p>Vos données ne sont jamais revendues et servent uniquement à améliorer votre expérience.</p>'],
];

$key = input('p');
$page = $pages[$key] ?? null;
if (!$page) { redirect('index.php'); }

$pageTitle = $page[0];
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => $page[0]]]) ?></div>
<section class="section-sm">
  <div class="container" style="max-width:760px">
    <div class="ic" style="width:64px;height:64px;border-radius:50%;background:var(--blush);display:grid;place-items:center;color:var(--rose);margin-bottom:16px"><?= icon($page[1]) ?></div>
    <h1><?php h($page[0]); ?></h1>
    <div class="prose" style="font-size:1.05rem;line-height:1.8;color:var(--text)"><?= $page[2] ?></div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
