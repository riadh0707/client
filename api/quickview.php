<?php
/** api/quickview.php — Fragment HTML d'aperçu rapide produit. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/icons.php';

$p = product_by_slug(trim((string) ($_GET['slug'] ?? '')));
if (!$p) { json_response(['ok' => false, 'error' => 'Introuvable'], 404); }

$img  = upload_url($p['primary_image'] ?? null, 'products', $p['name']);
$disc = discount_percent($p['price'], $p['old_price']);
$out  = (int) $p['stock'] <= 0;

ob_start(); ?>
<div class="qv-grid">
  <div class="qv-img"><img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>"></div>
  <div class="qv-body">
    <span class="pc-brand"><?= e($p['brand_name'] ?? 'Bella Rosa') ?></span>
    <h2 style="margin:6px 0 10px"><?= e($p['name']) ?></h2>
    <div class="pc-rating" style="margin-bottom:14px"><?= star_rating((float) $p['rating'], true) ?> <span>(<?= (int) $p['reviews_count'] ?> avis)</span></div>
    <div class="pd-price">
      <span class="now"><?= money($p['price']) ?></span>
      <?php if ($p['old_price'] > $p['price']): ?><span class="old"><?= money($p['old_price']) ?></span><span class="badge badge-sale">-<?= $disc ?>%</span><?php endif; ?>
    </div>
    <p style="color:var(--muted);font-size:.94rem"><?= e($p['short_desc']) ?></p>
    <div class="pd-buy">
      <?php if ($out): ?>
        <button class="btn btn-block" disabled style="opacity:.6">Épuisé</button>
      <?php else: ?>
        <button class="btn add-cart-btn" data-id="<?= (int) $p['id'] ?>"><?= icon('cart') ?> Ajouter au panier</button>
      <?php endif; ?>
      <button class="pc-action wish-btn" data-id="<?= (int) $p['id'] ?>" aria-label="Ajouter aux favoris" style="width:48px;height:48px"><?= icon('heart') ?></button>
    </div>
    <a href="<?= url('product.php?slug=' . e($p['slug'])) ?>" class="text-rose" style="font-weight:600;font-size:.9rem">Voir la fiche complète</a>
  </div>
</div>
<?php
json_response(['ok' => true, 'html' => ob_get_clean()]);
