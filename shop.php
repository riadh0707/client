<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$args = [
    'cat'    => input('cat'),
    'brand'  => input('brand'),
    'filter' => input('filter'),
    'min'    => input('min'),
    'max'    => input('max'),
    'avail'  => input('avail'),
    'q'      => input('q'),
    'sort'   => input('sort', 'popular'),
    'page'   => (int) input('page', '1'),
    'perPage'=> 12,
];

$res    = search_products($args);
$pg     = paginate($res['total'], $args['perPage'], $args['page']);
$cats   = all_categories();
$brands = all_brands();
$parents = array_filter($cats, fn($c) => $c['parent_id'] === null);

// Titre contextuel
$heading = 'Toute la boutique';
if ($args['q'])      { $heading = 'Résultats pour « ' . $args['q'] . ' »'; }
elseif ($args['filter'] === 'new')  { $heading = 'Nouveautés'; }
elseif ($args['filter'] === 'sale') { $heading = 'Promotions'; }
elseif ($args['filter'] === 'bestseller') { $heading = 'Meilleures ventes'; }
elseif ($args['cat']) {
    $cur = array_filter($cats, fn($c) => $c['slug'] === $args['cat']);
    if ($cur) { $heading = reset($cur)['name']; }
}

// Helper : construit une URL en conservant les paramètres.
function shop_url(array $override = []): string {
    $q = array_merge($_GET, $override);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return url('shop.php' . ($q ? '?' . http_build_query($q) : ''));
}

$pageTitle = $heading;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container">
  <?= breadcrumb([['label' => 'Boutique', 'url' => url('shop.php')], ['label' => $heading]]) ?>
</div>

<section class="section-sm">
  <div class="container">
    <div class="section-head" style="text-align:left;max-width:none;margin-bottom:26px">
      <h1 style="margin-bottom:6px"><?php h($heading); ?></h1>
      <p style="color:var(--muted)"><?= (int) $res['total'] ?> produit(s) — la beauté qui vous ressemble.</p>
    </div>

    <div class="shop-layout">
      <!-- Filtres -->
      <aside class="filters" id="shopFilters">
        <form method="get" id="filterForm">
          <?php if ($args['q']): ?><input type="hidden" name="q" value="<?php h($args['q']); ?>"><?php endif; ?>
          <div class="filter-group">
            <h4>Catégories</h4>
            <label><input type="radio" name="cat" value="" <?= $args['cat'] === '' ? 'checked' : '' ?> onchange="this.form.submit()"> Toutes</label>
            <?php foreach ($parents as $c): ?>
              <label><input type="radio" name="cat" value="<?php h($c['slug']); ?>" <?= $args['cat'] === $c['slug'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?php h($c['name']); ?></label>
            <?php endforeach; ?>
          </div>
          <div class="filter-group">
            <h4>Marques</h4>
            <label><input type="radio" name="brand" value="" <?= $args['brand'] === '' ? 'checked' : '' ?> onchange="this.form.submit()"> Toutes</label>
            <?php foreach ($brands as $b): ?>
              <label><input type="radio" name="brand" value="<?php h($b['slug']); ?>" <?= $args['brand'] === $b['slug'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?php h($b['name']); ?></label>
            <?php endforeach; ?>
          </div>
          <div class="filter-group">
            <h4>Prix (DA)</h4>
            <div style="display:flex;gap:8px">
              <input type="number" name="min" value="<?php h($args['min']); ?>" placeholder="Min" style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:9px;background:var(--surface);color:var(--text)">
              <input type="number" name="max" value="<?php h($args['max']); ?>" placeholder="Max" style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:9px;background:var(--surface);color:var(--text)">
            </div>
            <button class="btn btn-sm btn-block" style="margin-top:10px">Appliquer</button>
          </div>
          <div class="filter-group">
            <h4>Disponibilité</h4>
            <label><input type="checkbox" name="avail" value="1" <?= $args['avail'] ? 'checked' : '' ?> onchange="this.form.submit()"> En stock uniquement</label>
            <label><input type="checkbox" name="filter" value="sale" <?= $args['filter'] === 'sale' ? 'checked' : '' ?> onchange="this.form.submit()"> En promotion</label>
          </div>
          <a href="<?php h(url('shop.php')); ?>" class="btn btn-ghost btn-sm btn-block" style="margin-top:6px">Réinitialiser</a>
        </form>
      </aside>

      <!-- Résultats -->
      <div>
        <div class="shop-toolbar">
          <button class="btn btn-ghost btn-sm filter-toggle" onclick="document.getElementById('shopFilters').classList.toggle('open')"><?= icon('grid') ?> Filtres</button>
          <span class="count">Affichage <?= $pg['total'] ? $pg['offset'] + 1 : 0 ?>–<?= min($pg['offset'] + $pg['perPage'], $pg['total']) ?> sur <?= $pg['total'] ?></span>
          <form method="get" onchange="this.submit()">
            <?php foreach (['cat','brand','filter','min','max','avail','q'] as $k): if ($args[$k] !== ''): ?>
              <input type="hidden" name="<?= $k ?>" value="<?php h($args[$k]); ?>">
            <?php endif; endforeach; ?>
            <select name="sort" class="select">
              <option value="popular" <?= $args['sort']==='popular'?'selected':'' ?>>Popularité</option>
              <option value="new" <?= $args['sort']==='new'?'selected':'' ?>>Nouveautés</option>
              <option value="price_asc" <?= $args['sort']==='price_asc'?'selected':'' ?>>Prix croissant</option>
              <option value="price_desc" <?= $args['sort']==='price_desc'?'selected':'' ?>>Prix décroissant</option>
              <option value="rating" <?= $args['sort']==='rating'?'selected':'' ?>>Mieux notés</option>
            </select>
          </form>
        </div>

        <?php if (empty($res['items'])): ?>
          <div class="empty-state">
            <div class="ic"><?= icon('search') ?></div>
            <h3>Aucun produit trouvé</h3>
            <p style="color:var(--muted)">Essayez de modifier vos filtres ou votre recherche.</p>
            <a href="<?php h(url('shop.php')); ?>" class="btn">Voir tous les produits</a>
          </div>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($res['items'] as $p) { echo product_card($p); } ?>
          </div>

          <!-- Pagination -->
          <?php if ($pg['pages'] > 1): ?>
          <nav class="pagination">
            <a class="<?= $pg['current'] <= 1 ? 'disabled' : '' ?>" href="<?= shop_url(['page' => $pg['current'] - 1]) ?>"><?= icon('chevron','flip') ?></a>
            <?php for ($i = 1; $i <= $pg['pages']; $i++):
              if ($i == 1 || $i == $pg['pages'] || abs($i - $pg['current']) <= 2): ?>
                <a class="<?= $i == $pg['current'] ? 'active' : '' ?>" href="<?= shop_url(['page' => $i]) ?>"><?= $i ?></a>
              <?php elseif (abs($i - $pg['current']) == 3): ?>
                <span class="disabled">…</span>
              <?php endif;
            endfor; ?>
            <a class="<?= $pg['current'] >= $pg['pages'] ? 'disabled' : '' ?>" href="<?= shop_url(['page' => $pg['current'] + 1]) ?>"><?= icon('chevron') ?></a>
          </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
