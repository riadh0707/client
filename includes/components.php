<?php
/** components.php — Fragments d'interface réutilisables (DRY). */
require_once __DIR__ . '/icons.php';

/** Carte livre (grilles catalogue / accueil). */
function book_card(array $b): string
{
    $img    = upload_url($b['cover_image'] ?? null, 'covers', $b['title']);
    $link   = url('livre.php?slug=' . e($b['slug']));
    $disc   = discount_percent($b['price'], $b['old_price'] ?? null);
    $inWish = in_array($b['id'], $_SESSION['wishlist'] ?? [], true);
    $owned  = has_book_access((int) $b['id']);

    ob_start(); ?>
    <article class="book-card reveal" data-id="<?= (int) $b['id'] ?>">
      <div class="bc-media">
        <a href="<?= $link ?>"><img src="<?= e($img) ?>" alt="<?= e($b['title']) ?>" loading="lazy"></a>
        <div class="bc-badges">
          <?php if ($owned): ?><span class="badge badge-owned"><?= icon('check') ?> Acquis</span><?php endif; ?>
          <?php if ($disc > 0): ?><span class="badge badge-sale">-<?= $disc ?>%</span><?php endif; ?>
          <?php if (!empty($b['is_new'])): ?><span class="badge badge-new">Nouveau</span><?php endif; ?>
          <?php if (!empty($b['is_bestseller'])): ?><span class="badge badge-best">Best</span><?php endif; ?>
          <?php if (book_is_slides($b)): ?><span class="badge badge-format"><?= icon('presentation') ?> PowerPoint</span><?php endif; ?>
        </div>
        <div class="bc-actions">
          <button class="bc-action wish-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= (int) $b['id'] ?>" title="Ajouter à ma liste"><?= icon('heart') ?></button>
          <button class="bc-action quickview-btn" data-slug="<?= e($b['slug']) ?>" title="Aperçu rapide"><?= icon('eye') ?></button>
        </div>
        <div class="bc-quick">
          <a href="<?= url('lire.php?slug=' . e($b['slug'])) ?>" class="btn btn-block btn-sm btn-light"><?= icon('book-open') ?> Aperçu gratuit</a>
        </div>
      </div>
      <div class="bc-body">
        <span class="bc-author"><?= e($b['author'] ?? '') ?> · <?= (int) $b['pages_count'] ?> <?= e(book_unit($b)) ?></span>
        <h3 class="bc-title"><a href="<?= $link ?>"><?= e($b['title']) ?></a></h3>
        <div class="bc-rating"><?= star_rating((float) ($b['rating'] ?? 0)) ?> <span>(<?= (int) ($b['reviews_count'] ?? 0) ?>)</span></div>
        <div class="bc-price">
          <?php if ($owned): ?>
            <a href="<?= url('lire.php?slug=' . e($b['slug'])) ?>" class="now" style="text-decoration:none"><?= icon('book-open') ?> Lire</a>
          <?php else: ?>
            <span class="now"><?= e(book_price_label($b)) ?></span>
            <?php if (!empty($b['old_price']) && $b['old_price'] > $b['price']): ?><span class="old"><?= money($b['old_price']) ?></span><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

function breadcrumb(array $items): string
{
    $html = '<nav class="breadcrumb" aria-label="Fil d\'Ariane"><a href="' . url('index.php') . '">Accueil</a>';
    $last = array_key_last($items);
    foreach ($items as $i => $item) {
        $html .= '<span class="sep">' . icon('chevron') . '</span>';
        if ($i === $last || empty($item['url'])) { $html .= '<span>' . e($item['label']) . '</span>'; }
        else { $html .= '<a href="' . e($item['url']) . '">' . e($item['label']) . '</a>'; }
    }
    return $html . '</nav>';
}

function section_head(string $eyebrow, string $title, string $sub = ''): string
{
    $html  = '<div class="section-head reveal"><span class="eyebrow">' . e($eyebrow) . '</span>';
    $html .= '<h2>' . e($title) . '</h2>';
    if ($sub) { $html .= '<p>' . e($sub) . '</p>'; }
    $html .= '<div class="divider-orn"></div>';
    return $html . '</div>';
}

/** Pastille de statut de commande. */
function order_status_pill(string $status): string
{
    $labels = ['pending' => 'À payer', 'awaiting' => 'En validation', 'paid' => 'Payée', 'cancelled' => 'Annulée'];
    return '<span class="status-pill st-' . e($status) . '">' . e($labels[$status] ?? $status) . '</span>';
}
