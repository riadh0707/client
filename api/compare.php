<?php
/** api/compare.php — Tableau comparatif de produits (fragment HTML). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/icons.php';

$ids = array_slice(array_filter(array_map('intval', explode(',', (string) ($_GET['ids'] ?? '')))), 0, 4);
$products = products_by_ids($ids);
if (!$products) { json_response(['ok' => true, 'html' => '<p>Aucun produit.</p>']); }

$rows = [
    'Prix'        => fn($p) => money($p['price']),
    'Marque'      => fn($p) => e($p['brand_name'] ?? '—'),
    'Catégorie'   => fn($p) => e($p['category_name'] ?? '—'),
    'Note'        => fn($p) => star_rating((float) $p['rating']),
    'Avis'        => fn($p) => (int) $p['reviews_count'],
    'Volume'      => fn($p) => e($p['volume']),
    'Poids'       => fn($p) => (int) $p['weight'] . ' g',
    'Disponibilité' => fn($p) => (int) $p['stock'] > 0 ? '<span style="color:var(--success)">En stock</span>' : '<span style="color:var(--danger)">Épuisé</span>',
];

ob_start(); ?>
<div style="overflow-x:auto"><table class="table" style="min-width:640px">
  <thead><tr><th></th>
    <?php foreach ($products as $p): ?>
    <th style="min-width:180px">
      <img src="<?= e(upload_url($p['primary_image'] ?? null, 'products', $p['name'])) ?>" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;margin-bottom:8px">
      <div style="font-family:var(--font-serif);font-size:1rem;color:var(--text);text-transform:none;letter-spacing:0"><?= e($p['name']) ?></div>
    </th>
    <?php endforeach; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $label => $fn): ?>
    <tr><td style="font-weight:600"><?= e($label) ?></td>
      <?php foreach ($products as $p): ?><td><?= $fn($p) ?></td><?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
    <tr><td></td>
      <?php foreach ($products as $p): ?>
      <td><a href="<?= url('product.php?slug=' . e($p['slug'])) ?>" class="btn btn-sm">Voir</a></td>
      <?php endforeach; ?>
    </tr>
  </tbody>
</table></div>
<?php
json_response(['ok' => true, 'html' => ob_get_clean()]);
