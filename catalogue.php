<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$args = [
    'cat' => input('cat'), 'filter' => input('filter'), 'min' => input('min'), 'max' => input('max'),
    'q' => input('q'), 'sort' => input('sort', 'popular'), 'page' => (int) input('page', '1'), 'perPage' => 12,
];
$res  = search_books($args);
$pg   = paginate($res['total'], $args['perPage'], $args['page']);
$cats = all_categories();

$heading = 'Tous les livres';
if ($args['q']) { $heading = 'Résultats pour « ' . $args['q'] . ' »'; }
elseif ($args['filter'] === 'new') { $heading = 'Nouveautés'; }
elseif ($args['filter'] === 'sale') { $heading = 'Promotions'; }
elseif ($args['filter'] === 'bestseller') { $heading = 'Meilleures ventes'; }
elseif ($args['cat']) { $cur = array_filter($cats, fn($c) => $c['slug'] === $args['cat']); if ($cur) { $heading = reset($cur)['name']; } }

function shop_url(array $override = []): string {
    $q = array_filter(array_merge($_GET, $override), fn($v) => $v !== '' && $v !== null);
    return url('catalogue.php' . ($q ? '?' . http_build_query($q) : ''));
}

$pageTitle = $heading;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Catalogue', 'url' => url('catalogue.php')], ['label' => $heading]]) ?></div>

<section class="section-sm">
  <div class="container">
    <div style="margin-bottom:26px">
      <h1 style="margin-bottom:6px"><?php h($heading); ?></h1>
      <p style="color:var(--muted)"><?= (int) $res['total'] ?> titre(s), feuilletez un aperçu gratuit avant d'acheter.</p>
    </div>

    <div class="shop-layout">
      <aside class="filters" id="shopFilters">
        <form method="get" id="filterForm">
          <?php if ($args['q']): ?><input type="hidden" name="q" value="<?php h($args['q']); ?>"><?php endif; ?>
          <div class="filter-group">
            <h4>Rayons</h4>
            <label><input type="radio" name="cat" value="" <?= $args['cat'] === '' ? 'checked' : '' ?> onchange="this.form.submit()"> Tous les rayons</label>
            <?php foreach ($cats as $c): ?><label><input type="radio" name="cat" value="<?php h($c['slug']); ?>" <?= $args['cat'] === $c['slug'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?php h($c['name']); ?></label><?php endforeach; ?>
          </div>
          <div class="filter-group">
            <h4>Prix (DA)</h4>
            <div style="display:flex;gap:8px">
              <input type="number" name="min" value="<?php h($args['min']); ?>" placeholder="Min" style="width:100%;border:1.5px solid var(--border);border-radius:8px;padding:9px;background:var(--paper-2);color:var(--text)">
              <input type="number" name="max" value="<?php h($args['max']); ?>" placeholder="Max" style="width:100%;border:1.5px solid var(--border);border-radius:8px;padding:9px;background:var(--paper-2);color:var(--text)">
            </div>
            <button class="btn btn-sm btn-block" style="margin-top:10px">Appliquer</button>
          </div>
          <div class="filter-group">
            <h4>Offres</h4>
            <label><input type="checkbox" name="filter" value="sale" <?= $args['filter'] === 'sale' ? 'checked' : '' ?> onchange="this.form.submit()"> En promotion</label>
          </div>
          <a href="<?php h(url('catalogue.php')); ?>" class="btn btn-ghost btn-sm btn-block" style="margin-top:6px">Réinitialiser</a>
        </form>
      </aside>

      <div>
        <div class="shop-toolbar">
          <button class="btn btn-ghost btn-sm filter-toggle" onclick="document.getElementById('shopFilters').classList.toggle('open')"><?= icon('grid') ?> Filtres</button>
          <span class="count">Affichage <?= $pg['total'] ? $pg['offset'] + 1 : 0 ?>–<?= min($pg['offset'] + $pg['perPage'], $pg['total']) ?> sur <?= $pg['total'] ?></span>
          <form method="get" onchange="this.submit()">
            <?php foreach (['cat','filter','min','max','q'] as $k): if ($args[$k] !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?php h($args[$k]); ?>"><?php endif; endforeach; ?>
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
          <div class="empty-state"><div class="ic"><?= icon('search') ?></div><h3>Aucun livre trouvé</h3><p style="color:var(--muted)">Essayez de modifier vos filtres.</p><a href="<?php h(url('catalogue.php')); ?>" class="btn">Voir tous les livres</a></div>
        <?php else: ?>
          <div class="book-grid"><?php foreach ($res['items'] as $b) { echo book_card($b); } ?></div>
          <?php if ($pg['pages'] > 1): ?>
          <nav class="pagination">
            <a class="<?= $pg['current'] <= 1 ? 'disabled' : '' ?>" href="<?= shop_url(['page' => $pg['current'] - 1]) ?>"><?= icon('chevron','flip') ?></a>
            <?php for ($i = 1; $i <= $pg['pages']; $i++): if ($i == 1 || $i == $pg['pages'] || abs($i - $pg['current']) <= 2): ?>
              <a class="<?= $i == $pg['current'] ? 'active' : '' ?>" href="<?= shop_url(['page' => $i]) ?>"><?= $i ?></a>
            <?php elseif (abs($i - $pg['current']) == 3): ?><span class="disabled">…</span><?php endif; endfor; ?>
            <a class="<?= $pg['current'] >= $pg['pages'] ? 'disabled' : '' ?>" href="<?= shop_url(['page' => $pg['current'] + 1]) ?>"><?= icon('chevron') ?></a>
          </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
