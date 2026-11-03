<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$pageTitle = 'Comparateur';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Comparateur']]) ?></div>
<section class="section-sm">
  <div class="container">
    <h1 style="margin-bottom:18px">Comparateur de produits</h1>
    <div id="compareContent">
      <div class="empty-state"><div class="ic"><?= icon('compare') ?></div><h3>Chargement…</h3></div>
    </div>
  </div>
</section>

<script>
(async () => {
  const ids = JSON.parse(localStorage.getItem('compare') || '[]');
  const box = document.getElementById('compareContent');
  if (!ids.length) {
    box.innerHTML = '<div class="empty-state"><div class="ic">'+
      '<svg viewBox="0 0 24 24" width="42" height="42" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9 3v18M15 3v18M3 9h6M15 9h6"/></svg></div>'+
      '<h3>Aucun produit à comparer</h3><p style="color:var(--muted)">Ajoutez des produits via le bouton comparer sur les fiches.</p>'+
      '<a href="'+window.BELLA.base+'/shop.php" class="btn btn-lg">Parcourir la boutique</a></div>';
    return;
  }
  const r = await Bella.api('/api/compare.php?ids=' + ids.join(','));
  if (r.ok) box.innerHTML = r.html;
})();
</script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
