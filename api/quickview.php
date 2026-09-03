<?php
/** api/quickview.php — Aperçu rapide d'un livre (fragment HTML). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/icons.php';

$b = book_by_slug(trim((string) ($_GET['slug'] ?? '')));
if (!$b) { json_response(['ok' => false, 'error' => 'Introuvable'], 404); }

$img  = upload_url($b['cover_image'] ?? null, 'covers', $b['title']);
$disc = discount_percent($b['price'], $b['old_price']);
$owned = has_book_access((int) $b['id']);

ob_start(); ?>
<div style="display:grid;grid-template-columns:.8fr 1fr;gap:0">
  <div style="background:var(--paper-3)"><img src="<?= e($img) ?>" alt="<?= e($b['title']) ?>" style="width:100%;height:100%;object-fit:cover;min-height:360px"></div>
  <div style="padding:32px">
    <span class="bc-author"><?= e($b['author'] ?? '') ?> · <?= (int) $b['pages_count'] ?> <?= e(book_unit($b)) ?> · <?= e(book_format_label($b)) ?></span>
    <h2 style="margin:6px 0 10px"><?= e($b['title']) ?></h2>
    <div class="bc-rating" style="margin-bottom:14px"><?= star_rating((float) $b['rating'], true) ?> <span>(<?= (int) $b['reviews_count'] ?> avis)</span></div>
    <div class="bd-price">
      <span class="now"><?= e(book_price_label($b)) ?></span>
      <?php if ($b['old_price'] > $b['price']): ?><span class="old"><?= money($b['old_price']) ?></span><span class="badge badge-sale">-<?= $disc ?>%</span><?php endif; ?>
    </div>
    <p style="color:var(--muted);font-size:.96rem"><?= e($b['short_desc']) ?></p>
    <div class="bd-buy">
      <?php if ($owned): ?>
        <a href="<?= url('lire.php?slug=' . e($b['slug'])) ?>" class="btn"><?= icon('book-open') ?> Lire <?= book_is_slides($b) ? 'la présentation' : 'le livre' ?></a>
      <?php else: ?>
        <button class="btn add-cart-btn" data-id="<?= (int) $b['id'] ?>"><?= icon('cart') ?> Ajouter au panier</button>
      <?php endif; ?>
      <a href="<?= url('lire.php?slug=' . e($b['slug'])) ?>" class="btn btn-outline"><?= icon('eye') ?> Aperçu</a>
    </div>
    <a href="<?= url('livre.php?slug=' . e($b['slug'])) ?>" class="text-coffee" style="font-weight:600;font-size:.9rem">Voir la fiche complète →</a>
  </div>
</div>
<?php
json_response(['ok' => true, 'html' => ob_get_clean()]);
