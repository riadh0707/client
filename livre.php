<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$b = book_by_slug(input('slug'));
if (!$b) { http_response_code(404); $pageTitle = 'Introuvable'; require INCLUDES_PATH . '/header.php'; echo '<div class="container empty-state"><div class="ic">'.icon('book').'</div><h1>Livre introuvable</h1><a class="btn" href="'.url('catalogue.php').'">Retour au catalogue</a></div>'; require INCLUDES_PATH . '/footer.php'; exit; }

$owned   = has_book_access((int) $b['id']);
$cover   = upload_url($b['cover_image'] ?? null, 'covers', $b['title']);
$disc    = discount_percent($b['price'], $b['old_price']);
$inWish  = in_array($b['id'], $_SESSION['wishlist'] ?? [], true);
$reviews = reviews_for((int) $b['id']);
$related = books_related((int) $b['category_id'], (int) $b['id'], 4);
$tocLines = array_filter(array_map('trim', explode("\n", (string) $b['toc'])));
$isSlides = book_is_slides($b);
$unit     = book_unit($b);
$noun     = $isSlides ? 'cette présentation' : 'ce livre';

$pageTitle = $b['title'];
$pageDescription = excerpt($b['short_desc'], 155);
$ogImage = $cover;
require_once INCLUDES_PATH . '/header.php';
?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Book","name":<?= json_encode($b['title']) ?>,"author":{"@type":"Person","name":<?= json_encode($b['author']) ?>},"numberOfPages":<?= (int)$b['pages_count'] ?>,"aggregateRating":{"@type":"AggregateRating","ratingValue":"<?= $b['rating'] ?>","reviewCount":"<?= (int)$b['reviews_count'] ?>"},"offers":{"@type":"Offer","price":"<?= $b['price'] ?>","priceCurrency":"DZD"}}
</script>

<div class="container"><?= breadcrumb([
    ['label' => 'Catalogue', 'url' => url('catalogue.php')],
    ['label' => $b['category_name'] ?? 'Livres', 'url' => url('catalogue.php?cat=' . ($b['category_slug'] ?? ''))],
    ['label' => $b['title']],
]) ?></div>

<section class="section-sm">
  <div class="container">
    <div class="bd-layout">
      <div class="bd-cover">
        <div class="cov">
          <img src="<?php h($cover); ?>" alt="<?php h($b['title']); ?>">
          <?php if ($disc > 0): ?><span class="badge badge-sale" style="position:absolute;top:14px;left:14px">-<?= $disc ?>%</span><?php endif; ?>
        </div>
        <a href="<?php h(url('lire.php?slug=' . $b['slug'])); ?>" class="btn btn-outline btn-block" style="margin-top:16px"><?= $isSlides ? icon('presentation') : icon('book-open') ?> <?= $isSlides ? 'Voir l\'aperçu gratuit' : 'Feuilleter l\'aperçu gratuit' ?></a>
      </div>

      <div class="bd-info">
        <h1><?php h($b['title']); ?></h1>
        <div class="bd-author">par <?php h($b['author']); ?></div>
        <div class="bd-meta-row">
          <?= star_rating((float) $b['rating'], true) ?>
          <span>· <?= (int) $b['reviews_count'] ?> avis</span>
          <span>· <?= $isSlides ? icon('presentation') : icon('pages') ?> <?= (int) $b['pages_count'] ?> <?= e($unit) ?></span>
          <span>· <?= e(book_format_label($b)) ?></span>
          <span>· <?php h($b['language']); ?></span>
        </div>

        <?php if ($owned): ?>
          <div class="bd-owned-banner"><?= icon('unlock') ?> Vous possédez <?= e($noun) ?> — bonne lecture !</div>
          <div class="bd-buy">
            <a href="<?php h(url('lire.php?slug=' . $b['slug'])); ?>" class="btn btn-lg"><?= icon('book-open') ?> <?= $isSlides ? 'Voir la présentation complète' : 'Lire le livre complet' ?></a>
            <?php if ($isSlides): ?><a href="<?php h(url('book-pdf.php?slug=' . urlencode($b['slug']) . '&mode=full&download=1')); ?>" class="btn btn-outline btn-lg"><?= icon('download') ?> Télécharger le .pptx</a><?php endif; ?>
          </div>
        <?php else: ?>
          <div class="bd-price">
            <span class="now"><?= e(book_price_label($b)) ?></span>
            <?php if ($b['old_price'] > $b['price']): ?><span class="old"><?= money($b['old_price']) ?></span><span class="badge badge-sale">Économisez <?= money($b['old_price'] - $b['price']) ?></span><?php endif; ?>
          </div>
          <p class="bd-desc"><?php h($b['short_desc']); ?></p>
          <div class="bd-buy">
            <button class="btn btn-lg add-cart-btn" data-id="<?= (int) $b['id'] ?>" style="flex:1;min-width:200px"><?= icon('cart') ?> Ajouter au panier</button>
            <a href="<?php h(url('checkout.php?buy=' . $b['id'])); ?>" class="btn btn-gold btn-lg">Acheter maintenant</a>
            <button class="bc-action wish-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= (int) $b['id'] ?>" style="width:52px;height:52px" title="Ma liste"><?= icon('bookmark') ?></button>
          </div>
        <?php endif; ?>

        <div class="bd-features">
          <div class="bd-feature"><?= icon('book-open') ?> <div><strong>Aperçu gratuit</strong><br><?= (int) $b['preview_pages'] ?> premières <?= e($unit) ?></div></div>
          <div class="bd-feature"><?= icon('wallet') ?> <div><strong>Paiement BaridiMob</strong><br>Simple et sécurisé</div></div>
          <div class="bd-feature"><?= $isSlides ? icon('presentation') : icon('unlock') ?> <div><strong><?= $isSlides ? 'Diapositives en ligne' : 'Lecture en ligne' ?></strong><br>Après validation du paiement</div></div>
          <div class="bd-feature"><?= icon('refresh') ?> <div><strong>Accès à vie</strong><br>Dans votre bibliothèque</div></div>
        </div>
      </div>
    </div>

    <div class="bd-tabs">
      <div class="tab-nav">
        <button class="active" data-tab="desc">Description</button>
        <button data-tab="toc">Sommaire</button>
        <button data-tab="reviews">Avis (<?= count($reviews) ?>)</button>
      </div>
      <div class="tab-panel active" data-panel="desc">
        <p style="max-width:760px;color:var(--muted);font-family:var(--font-serif);font-size:1.18rem"><?php h($b['long_desc']); ?></p>
        <table class="spec-table" style="max-width:520px;margin-top:20px">
          <tr><td>Auteur</td><td><?php h($b['author']); ?></td></tr>
          <tr><td>Rayon</td><td><?php h($b['category_name'] ?? '—'); ?></td></tr>
          <tr><td><?= $isSlides ? 'Diapositives' : 'Pages' ?></td><td><?= (int) $b['pages_count'] ?></td></tr>
          <tr><td>Langue</td><td><?php h($b['language']); ?></td></tr>
          <tr><td>Format</td><td><?= e(book_format_label($b)) ?> · lecture en ligne<?= $isSlides ? ' + téléchargement après achat' : '' ?></td></tr>
        </table>
      </div>
      <div class="tab-panel" data-panel="toc">
        <?php if ($tocLines): ?>
          <ul class="toc-list" style="max-width:820px">
            <?php foreach ($tocLines as $line): ?><li><?php h($line); ?></li><?php endforeach; ?>
          </ul>
        <?php else: ?><p style="color:var(--muted)">Sommaire non renseigné.</p><?php endif; ?>
        <div style="margin-top:20px"><a href="<?php h(url('lire.php?slug=' . $b['slug'])); ?>" class="btn btn-outline"><?= icon('book-open') ?> Voir l'aperçu</a></div>
      </div>
      <div class="tab-panel" data-panel="reviews">
        <div style="max-width:760px">
          <?php if (empty($reviews)): ?><p style="color:var(--muted)">Aucun avis pour le moment.</p>
          <?php else: foreach ($reviews as $r): ?>
            <div class="review-item">
              <div class="review-head">
                <span class="review-avatar"><?php h(initials($r['author_name'])); ?></span>
                <div><strong><?php h($r['author_name']); ?></strong><br><?= star_rating((float) $r['rating']) ?></div>
                <span style="margin-left:auto;color:var(--muted);font-size:.8rem"><?php h(time_ago($r['created_at'])); ?></span>
              </div>
              <?php if ($r['title']): ?><strong style="display:block"><?php h($r['title']); ?></strong><?php endif; ?>
              <p style="color:var(--muted);margin:6px 0 0"><?php h($r['body']); ?></p>
            </div>
          <?php endforeach; endif; ?>

          <h3 style="margin-top:30px">Laisser un avis</h3>
          <form method="post" action="<?php h(url('review-submit.php')); ?>" style="margin-top:14px">
            <?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int) $b['id'] ?>">
            <div class="form-row">
              <div class="field"><label>Votre nom</label><input name="author_name" required value="<?= e(current_user()['first_name'] ?? '') ?>"></div>
              <div class="field"><label>Note</label><select name="rating" class="select" style="width:100%"><option value="5">★★★★★ Excellent</option><option value="4">★★★★ Très bien</option><option value="3">★★★ Bien</option><option value="2">★★ Moyen</option><option value="1">★ Décevant</option></select></div>
            </div>
            <div class="field"><label>Titre</label><input name="title" placeholder="Résumez votre avis"></div>
            <div class="field"><label>Votre avis</label><textarea name="body" required placeholder="Partagez votre expérience…"></textarea></div>
            <button class="btn">Publier mon avis</button>
          </form>
        </div>
      </div>
    </div>

    <?php if ($related): ?>
    <div style="margin-top:60px">
      <?= section_head('À découvrir aussi', 'Dans le même rayon') ?>
      <div class="book-grid"><?php foreach ($related as $rb) { echo book_card($rb); } ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
