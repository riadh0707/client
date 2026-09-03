<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$faqs = [
  'Aperçu & lecture' => [
    ['Puis-je lire avant d\'acheter ?', 'Oui ! Chaque titre propose un aperçu gratuit : les premières pages d\'un livre PDF, ou les premières diapositives d\'une présentation (le nombre est fixé par notre équipe). Cliquez sur « Aperçu gratuit ».'],
    ['Quels formats proposez-vous ?', 'Deux formats. Les livres PDF s\'ouvrent dans la visionneuse du navigateur ; les présentations PowerPoint (.pptx) sont affichées diapositive par diapositive, avec leurs titres, listes, tableaux et images — sans avoir PowerPoint installé.'],
    ['Comment lire un titre acheté ?', 'Après validation de votre paiement, le titre s\'ouvre en lecture en ligne dans « Ma bibliothèque ». Aucun logiciel requis.'],
    ['Le fichier est-il téléchargeable ?', 'Les livres PDF se lisent en ligne, pour protéger le travail des auteurs. Les présentations PowerPoint achetées peuvent en plus être téléchargées au format .pptx. Votre accès est permanent.'],
  ],
  'Paiement BaridiMob' => [
    ['Comment payer ?', 'Ajoutez le titre au panier, validez, puis effectuez un virement BaridiMob vers le RIP affiché. Indiquez ensuite la référence de la transaction.'],
    ['Quand mon accès est-il activé ?', 'Dès que notre équipe confirme la réception de votre virement (généralement sous 24h). Vous en êtes informé.'],
    ['Le paiement par carte arrive-t-il ?', 'L\'architecture est prête : Edahabia, CIB et carte bancaire seront ajoutés dès que l\'API BaridiMob/paiement sera disponible.'],
  ],
  'Mon compte' => [
    ['Dois-je créer un compte ?', 'Oui, pour conserver vos titres dans votre bibliothèque et y accéder à tout moment.'],
    ['J\'ai payé mais pas encore d\'accès ?', 'Votre commande est « en validation ». Elle passe à « Payée » dès confirmation. Contactez-nous si besoin.'],
  ],
];
$pageTitle = 'FAQ';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'FAQ']]) ?></div>
<section class="section-sm"><div class="container" style="max-width:820px">
  <div class="section-head"><span class="eyebrow">Aide</span><h1>Questions fréquentes</h1><div class="divider-orn"></div></div>
  <?php $fid = 0; foreach ($faqs as $group => $items): ?>
  <h3 style="margin:26px 0 10px"><?php h($group); ?></h3>
  <?php foreach ($items as $f): $fid++; ?>
  <div class="card-panel faq-item">
    <button type="button" class="faq-q" aria-expanded="false" aria-controls="faq-a-<?= $fid ?>">
      <strong><?php h($f[0]); ?></strong>
      <span class="faq-plus"><?= icon('plus') ?></span>
    </button>
    <div class="faq-a" id="faq-a-<?= $fid ?>"><p><?php h($f[1]); ?></p></div>
  </div>
  <?php endforeach; endforeach; ?>
  <div class="newsletter" style="margin-top:34px"><h2>Une autre question ?</h2><p>Écrivez-nous, nous sommes là pour vous.</p><a href="<?php h(url('contact.php')); ?>" class="btn btn-gold btn-lg" style="margin-top:10px">Nous contacter</a></div>
</div></section>
<style>
  .faq-item { margin-bottom: 10px; padding: 0; overflow: hidden; }
  .faq-q { display: flex; justify-content: space-between; align-items: center; gap: 12px;
           width: 100%; padding: 18px 22px; background: none; border: 0; cursor: pointer;
           text-align: left; color: inherit; font: inherit; }
  .faq-q strong { font-weight: 600; }
  .faq-plus { color: var(--coffee); flex: none; display: inline-flex; transition: transform .3s ease; }
  .faq-plus svg { width: 20px; height: 20px; }
  /* Repli sans JS : le contenu reste lisible si les scripts sont désactivés. */
  .faq-a { padding: 0 22px; }
  .faq-a p { color: var(--muted); padding-bottom: 18px; margin: 0; }
  .js .faq-a { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s ease; }
  .js .faq-a > p { overflow: hidden; }
  .js .faq-item.open .faq-a { grid-template-rows: 1fr; }
  .faq-item.open .faq-plus { transform: rotate(45deg); }
</style>
<script>
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var open = item.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
</script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
