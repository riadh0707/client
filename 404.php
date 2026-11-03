<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';
http_response_code(404);
$pageTitle = 'Page introuvable';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section">
  <div class="container center" style="max-width:560px">
    <div style="font-family:var(--font-serif);font-size:7rem;font-weight:700;background:var(--grad);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;line-height:1">404</div>
    <h1>Oups, cette page s'est envolée</h1>
    <p style="color:var(--muted)">La page que vous cherchez n'existe pas ou a été déplacée. Retrouvez vos produits préférés dans notre boutique.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px">
      <a href="<?php h(url('index.php')); ?>" class="btn btn-lg">Retour à l'accueil</a>
      <a href="<?php h(url('shop.php')); ?>" class="btn btn-outline btn-lg">Voir la boutique</a>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
