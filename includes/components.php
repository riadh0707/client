<?php
/**
 * components.php — Fragments d'interface réutilisables (DRY).
 */
require_once __DIR__ . '/icons.php';

/** Carte produit (grille boutique, accueil, carrousels…). */
function product_card(array $p): string
{
    $img   = upload_url($p['primary_image'] ?? null, 'products', $p['name']);
    $alt   = !empty($p['second_image']) ? upload_url($p['second_image'], 'products', $p['name']) : null;
    $link  = url('product.php?slug=' . e($p['slug']));
    $disc  = discount_percent($p['price'], $p['old_price'] ?? null);
    $inWish = in_array($p['id'], $_SESSION['wishlist'] ?? [], true);
    $out   = (int) ($p['stock'] ?? 1) <= 0;

    ob_start(); ?>
    <article class="product-card" data-id="<?= (int) $p['id'] ?>">
      <div class="pc-media">
        <a href="<?= $link ?>"><img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy"><?php if ($alt): ?><img class="alt" src="<?= e($alt) ?>" alt="" loading="lazy" aria-hidden="true"><?php endif; ?></a>
        <div class="pc-badges">
          <?php if ($out): ?><span class="badge badge-out">Épuisé</span><?php endif; ?>
          <?php if ($disc > 0): ?><span class="badge badge-sale">-<?= $disc ?>%</span><?php endif; ?>
          <?php if (!empty($p['is_new'])): ?><span class="badge badge-new">Nouveau</span><?php endif; ?>
          <?php if (!empty($p['is_bestseller'])): ?><span class="badge badge-best">Top vente</span><?php endif; ?>
        </div>
        <div class="pc-actions">
          <button class="pc-action wish-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= (int) $p['id'] ?>" aria-label="Ajouter aux favoris" title="Favoris"><?= icon('heart') ?></button>
          <button class="pc-action quickview-btn" data-slug="<?= e($p['slug']) ?>" aria-label="Aperçu rapide" title="Aperçu rapide"><?= icon('eye') ?></button>
          <button class="pc-action compare-btn" data-id="<?= (int) $p['id'] ?>" aria-label="Comparer" title="Comparer"><?= icon('compare') ?></button>
        </div>
        <div class="pc-quick">
          <?php if ($out): ?>
            <button class="btn btn-block btn-sm" disabled style="opacity:.6">Indisponible</button>
          <?php else: ?>
            <button class="btn btn-block btn-sm add-cart-btn" data-id="<?= (int) $p['id'] ?>"><?= icon('cart') ?> Ajouter au panier</button>
          <?php endif; ?>
        </div>
      </div>
      <div class="pc-body">
        <span class="pc-brand"><?= e($p['brand_name'] ?? 'Bella Rosa') ?></span>
        <h3 class="pc-name"><a href="<?= $link ?>"><?= e($p['name']) ?></a></h3>
        <div class="pc-rating"><?= star_rating((float) ($p['rating'] ?? 0)) ?> <span>(<?= (int) ($p['reviews_count'] ?? 0) ?>)</span></div>
        <div class="pc-price">
          <span class="now"><?= money($p['price']) ?></span>
          <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price']): ?>
            <span class="old"><?= money($p['old_price']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

/** Bandeau fil d'Ariane. */
function breadcrumb(array $items): string
{
    $html = '<nav class="breadcrumb" aria-label="Fil d\'Ariane"><a href="' . url('index.php') . '">Accueil</a>';
    $last = array_key_last($items);
    foreach ($items as $i => $item) {
        $html .= '<span class="sep">' . icon('chevron') . '</span>';
        if ($i === $last || empty($item['url'])) {
            $html .= '<span>' . e($item['label']) . '</span>';
        } else {
            $html .= '<a href="' . e($item['url']) . '">' . e($item['label']) . '</a>';
        }
    }
    return $html . '</nav>';
}

/** Pastille de statut de commande (libellé FR + couleur). */
function order_status_pill(string $status): string
{
    $labels = [
        'pending'   => 'En attente',
        'confirmed' => 'Confirmée',
        'shipped'   => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];
    $label = $labels[$status] ?? $status;
    return '<span class="status-pill st-' . e($status) . '">' . e($label) . '</span>';
}

/** Titre de section (le 1er argument, ancien surtitre, est conservé pour compatibilité). */
function section_head(string $eyebrow, string $title, string $sub = ''): string
{
    $html  = '<div class="section-head">';
    $html .= '<h2>' . e($title) . '</h2>';
    if ($sub) { $html .= '<p>' . e($sub) . '</p>'; }
    return $html . '</div>';
}

/** En-tête de section aligné à gauche avec lien « voir plus ». */
function section_row(string $title, string $sub = '', ?string $href = null, string $label = 'Tout voir'): string
{
    $html = '<div class="section-row"><div><h2>' . e($title) . '</h2>';
    if ($sub) { $html .= '<p>' . e($sub) . '</p>'; }
    $html .= '</div>';
    if ($href) { $html .= '<a class="link-more" href="' . e($href) . '">' . e($label) . ' ' . icon('arrow') . '</a>'; }
    return $html . '</div>';
}
