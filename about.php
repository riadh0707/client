<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$pageTitle = 'À propos';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'À propos']]) ?></div>

<div class="container">
  <div class="page-hero" style="background-image:url('<?php h(upload_url('banner-01.jpg', 'banners')); ?>');min-height:340px">
    <div style="max-width:620px">
      <h1>La beauté algérienne, sublimée</h1>
      <p>Bella Rosa est née d'une conviction : chaque femme mérite des produits de beauté d'exception, authentiques et accessibles, livrés jusqu'à sa porte, partout en Algérie.</p>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="editorial">
      <div class="ph"><img src="<?php h(upload_url('banner-06.jpg', 'banners')); ?>" alt="Femme se maquillant devant un miroir" loading="lazy"></div>
      <div>
        <h2>Une sélection experte, une passion sincère</h2>
        <p>Depuis notre création, nous parcourons le monde de la cosmétique pour vous offrir le meilleur du maquillage, des soins, des parfums et des accessoires. Chaque produit est choisi pour sa qualité, son efficacité et son plaisir d'utilisation.</p>
        <p>Notre équipe de passionnées vous accompagne avec des conseils personnalisés et un service client irréprochable.</p>
        <div class="pd-features" style="margin-top:20px">
          <div class="pd-feature"><?= icon('sparkles') ?> <div><strong>+120 produits</strong><br>Sélection premium</div></div>
          <div class="pd-feature"><?= icon('user') ?> <div><strong>+5 000 clientes</strong><br>Nous font confiance</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-alt">
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
      <div class="stat-card"><div style="color:var(--rose);margin-bottom:10px"><?= icon($v[0]) ?></div><strong><?php h($v[1]); ?></strong><p style="color:var(--muted);font-size:.9rem;margin:6px 0 0"><?php h($v[2]); ?></p></div>
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
      <div class="card-panel" style="display:flex;gap:14px;align-items:flex-start"><span class="review-avatar" style="background:var(--rose)"><?= icon('check') ?></span><div><strong><?php h($en[0]); ?></strong><p style="color:var(--muted);margin:4px 0 0;font-size:.92rem"><?php h($en[1]); ?></p></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
