<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$pageTitle = 'À propos';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'À propos']]) ?></div>
<section class="hero" style="min-height:auto"><div class="container" style="position:relative;z-index:2;padding:50px 24px;text-align:center;max-width:760px">
  <span class="eyebrow">Notre histoire</span><h1>Le savoir, transmis avec cœur</h1>
  <p style="font-size:1.2rem;color:var(--muted);font-family:var(--font-serif)">La Bibliothèque Numérique rassemble des livres et des présentations à lire en ligne : culture, formation, développement personnel et savoir-faire professionnel. Un aperçu gratuit pour chaque titre, puis la lecture intégrale après votre achat.</p>
</div></section>
<section class="section"><div class="container">
  <?= section_head('Nos engagements', 'Pourquoi nous choisir') ?>
  <div class="trust-grid" style="gap:24px">
    <?php foreach ([['book-open','Aperçu gratuit','Feuilletez avant d\'acheter, en toute confiance.'],['unlock','Lecture à vie','Vos livres restent dans votre bibliothèque en ligne.'],['wallet','Paiement local','Par BaridiMob, simple et adapté à l\'Algérie.'],['presentation','Livres & présentations','Formats PDF et PowerPoint, lisibles directement en ligne.']] as $v): ?>
    <div class="stat-card reveal center"><div style="color:var(--coffee);margin-bottom:10px"><?= icon($v[0]) ?></div><strong><?php h($v[1]); ?></strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0"><?php h($v[2]); ?></p></div>
    <?php endforeach; ?>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
