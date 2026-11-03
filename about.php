<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$pageTitle = 'À propos';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'À propos']]) ?></div>

<section class="hero" style="min-height:auto">
  <span class="blob b1"></span>
  <div class="container" style="position:relative;z-index:2;padding:50px 24px;text-align:center;max-width:760px">
    <span class="eyebrow">Notre histoire</span>
    <h1>La beauté algérienne, sublimée</h1>
    <p style="font-size:1.12rem;color:var(--muted)">Bella Rosa est née d'une conviction : chaque femme mérite des produits de beauté d'exception, authentiques et accessibles, livrés jusqu'à sa porte, partout en Algérie.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="pd-layout">
      <div class="pd-main-img" style="cursor:default"><img src="<?php h(upload_url(null, 'banners', 'Notre histoire')); ?>" alt="Bella Rosa"></div>
      <div>
        <span class="eyebrow">Qui sommes-nous</span>
        <h2>Une sélection experte, une passion sincère</h2>
        <p style="color:var(--muted)">Depuis notre création, nous parcourons le monde de la cosmétique pour vous offrir le meilleur du maquillage, des soins, des parfums et des accessoires. Chaque produit est choisi pour sa qualité, son efficacité et son plaisir d'utilisation.</p>
        <p style="color:var(--muted)">Notre équipe de passionnées vous accompagne avec des conseils personnalisés et un service client irréprochable.</p>
        <div class="pd-features" style="margin-top:20px">
          <div class="pd-feature"><?= icon('sparkles') ?> <div><strong>+120 produits</strong><br>Sélection premium</div></div>
          <div class="pd-feature"><?= icon('user') ?> <div><strong>+5 000 clientes</strong><br>Nous font confiance</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--bg-alt)">
  <div class="container">
    <?= section_head('Nos valeurs', 'Pourquoi nous choisir') ?>
    <div class="trust-grid" style="gap:24px">
      <?php
      $vals = [
        ['leaf', 'Beauté clean', 'Des formules respectueuses de votre peau et de la planète.'],
        ['shield', 'Authenticité garantie', 'Uniquement des produits originaux, directement des marques.'],
        ['truck', 'Livraison 58 wilayas', 'Nous livrons partout en Algérie, rapidement et en toute sécurité.'],
        ['chat', 'Conseil expert', 'Une équipe passionnée à votre écoute 7j/7.'],
      ];
      foreach ($vals as $v): ?>
      <div class="stat-card reveal"><div style="color:var(--rose);margin-bottom:10px"><?= icon($v[0]) ?></div><strong><?php h($v[1]); ?></strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0"><?php h($v[2]); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= section_head('Nos engagements', 'Ce que nous vous garantissons') ?>
    <div class="stat-cards" style="grid-template-columns:repeat(2,1fr)">
      <?php
      $engage = [
        ['Produits 100% authentiques', 'Chaque article provient de circuits officiels et est garanti original.'],
        ['Satisfait ou remboursé', 'Retour gratuit sous 14 jours si vous changez d\'avis.'],
        ['Paiement à la livraison', 'Payez en toute confiance uniquement à réception.'],
        ['Emballage soigné & discret', 'Vos produits arrivent protégés et joliment présentés.'],
      ];
      foreach ($engage as $en): ?>
      <div class="card-panel reveal" style="display:flex;gap:14px;align-items:flex-start"><span class="review-avatar" style="background:var(--grad)"><?= icon('check') ?></span><div><strong><?php h($en[0]); ?></strong><p style="color:var(--muted);margin:4px 0 0;font-size:.92rem"><?php h($en[1]); ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
