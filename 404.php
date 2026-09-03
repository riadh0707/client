<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';
http_response_code(404);
$pageTitle = 'Page introuvable';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section"><div class="container center" style="max-width:560px">
  <div style="font-family:var(--font-display);font-size:7rem;font-weight:700;color:var(--coffee);line-height:1">404</div>
  <h1>Cette page a été mangée</h1>
  <p style="color:var(--muted)">La page que vous cherchez n'existe pas. Retournez feuilleter nos livres.</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px"><a href="<?php h(url('index.php')); ?>" class="btn btn-lg">Accueil</a><a href="<?php h(url('catalogue.php')); ?>" class="btn btn-outline btn-lg">Le catalogue</a></div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
