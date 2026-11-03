<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';

$faqs = [
    'Commandes & Livraison' => [
        ['Quels sont les délais de livraison ?', 'La livraison prend généralement 2 à 5 jours ouvrés selon votre wilaya. Vous recevez un appel de confirmation avant l\'expédition.'],
        ['Quels sont les frais de livraison ?', 'La livraison est à ' . money(setting('shipping_flat', 600)) . '. Elle est offerte dès ' . money(setting('free_shipping_min', 8000)) . ' d\'achat.'],
        ['Livrez-vous partout en Algérie ?', 'Oui ! Nous livrons dans les 58 wilayas, à domicile ou en point de retrait.'],
        ['Comment suivre ma commande ?', 'Connectez-vous à votre espace client, rubrique « Mes commandes », pour suivre son statut en temps réel.'],
    ],
    'Paiement' => [
        ['Quels moyens de paiement acceptez-vous ?', 'Paiement à la livraison (espèces), BaridiMob et versement CCP/CIB. Les paiements par carte (Edahabia, CIB, Visa) arrivent bientôt.'],
        ['Le paiement à la livraison est-il sûr ?', 'Absolument. Vous ne payez qu\'à réception de votre colis, après vérification.'],
    ],
    'Produits & Retours' => [
        ['Vos produits sont-ils authentiques ?', 'Oui, 100% authentiques et issus de circuits officiels. Nous garantissons l\'originalité de chaque article.'],
        ['Puis-je retourner un produit ?', 'Vous disposez de 14 jours pour retourner un produit non ouvert. Contactez notre service client pour organiser le retour.'],
        ['Comment choisir le bon produit ?', 'Nos fiches produits détaillent composition et conseils. Notre équipe est également disponible pour vous guider.'],
    ],
    'Mon compte' => [
        ['Dois-je créer un compte pour commander ?', 'Non, vous pouvez commander en tant qu\'invitée. Mais un compte vous permet de suivre vos commandes et cumuler des points fidélité.'],
        ['Comment fonctionne le programme fidélité ?', 'Vous gagnez 1 point par tranche de 100 DA dépensée. Cumulez des points pour débloquer des bons de réduction.'],
    ],
];

$pageTitle = 'FAQ';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'FAQ']]) ?></div>
<section class="section-sm">
  <div class="container" style="max-width:820px">
    <div class="section-head"><h1>Questions fréquentes</h1><p>Trouvez rapidement une réponse à vos questions.</p></div>

    <div class="field" style="margin-bottom:24px"><input type="search" id="faqSearch" placeholder="Rechercher une question…" aria-label="Rechercher une question" onkeyup="faqFilter(this.value)"></div>

    <?php foreach ($faqs as $group => $items): ?>
    <h3 style="margin:26px 0 10px"><?php h($group); ?></h3>
    <?php foreach ($items as $f): ?>
    <div class="card-panel faq-item" style="margin-bottom:10px;padding:0;cursor:pointer" onclick="this.classList.toggle('open')">
      <div style="padding:18px 22px;display:flex;justify-content:space-between;align-items:center;gap:12px">
        <strong class="faq-q"><?php h($f[0]); ?></strong>
        <span class="faq-plus" style="color:var(--rose);flex:none"><?= icon('plus') ?></span>
      </div>
      <div class="faq-a" style="max-height:0;overflow:hidden;transition:max-height .3s ease;padding:0 22px">
        <p style="color:var(--muted);padding-bottom:18px"><?php h($f[1]); ?></p>
      </div>
    </div>
    <?php endforeach; endforeach; ?>

    <div class="newsletter" style="margin-top:34px">
      <h2>Vous ne trouvez pas votre réponse ?</h2>
      <p>Notre équipe est là pour vous aider.</p>
      <a href="<?php h(url('contact.php')); ?>" class="btn btn-lg" style="margin-top:10px">Contactez-nous</a>
    </div>
  </div>
</section>

<style>.faq-item.open .faq-a{max-height:240px}.faq-item.open .faq-plus{transform:rotate(45deg);transition:transform .3s}</style>
<script>
function faqFilter(q){ q=q.toLowerCase(); document.querySelectorAll('.faq-item').forEach(el=>{ el.style.display = el.querySelector('.faq-q').textContent.toLowerCase().includes(q) ? '' : 'none'; }); }
</script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
