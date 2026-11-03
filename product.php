<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/components.php';

$p = product_by_slug(input('slug'));
if (!$p) { http_response_code(404); $pageTitle = 'Produit introuvable'; require INCLUDES_PATH . '/header.php'; echo '<div class="container empty-state"><div class="ic">'.icon('search').'</div><h1>Produit introuvable</h1><a class="btn" href="'.url('shop.php').'">Retour à la boutique</a></div>'; require INCLUDES_PATH . '/footer.php'; exit; }

track_recently_viewed((int) $p['id']);

$gallery = Database::all('SELECT file FROM product_images WHERE product_id = ? ORDER BY position', [$p['id']]);
$images  = $gallery ? array_column($gallery, 'file')
                    : [null, null, null, null]; // 4 variantes placeholder
$reviews = reviews_for((int) $p['id']);
$related = products_related((int) $p['category_id'], (int) $p['id'], 4);
$recent  = products_by_ids(recently_viewed_ids((int) $p['id']));
$disc    = discount_percent($p['price'], $p['old_price']);
$out     = (int) $p['stock'] <= 0;
$inWish  = in_array($p['id'], $_SESSION['wishlist'] ?? [], true);

$pageTitle = $p['name'];
$pageDescription = excerpt($p['short_desc'], 155);
$ogImage = upload_url($p['primary_image'] ?? null, 'products', $p['name']);
require_once INCLUDES_PATH . '/header.php';
?>
<!-- Schema.org Product -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Product","name":<?= json_encode($p['name']) ?>,"sku":<?= json_encode($p['sku']) ?>,"brand":{"@type":"Brand","name":<?= json_encode($p['brand_name'] ?? 'Bella Rosa') ?>},"aggregateRating":{"@type":"AggregateRating","ratingValue":"<?= $p['rating'] ?>","reviewCount":"<?= (int)$p['reviews_count'] ?>"},"offers":{"@type":"Offer","price":"<?= $p['price'] ?>","priceCurrency":"DZD","availability":"<?= $out ? 'OutOfStock' : 'InStock' ?>"}}
</script>

<div class="container">
  <?= breadcrumb([
      ['label' => 'Boutique', 'url' => url('shop.php')],
      ['label' => $p['category_name'] ?? 'Produits', 'url' => url('shop.php?cat=' . ($p['category_slug'] ?? ''))],
      ['label' => $p['name']],
  ]) ?>
</div>

<section class="section-sm">
  <div class="container">
    <div class="pd-layout">
      <!-- Galerie -->
      <div class="pd-gallery">
        <div class="pd-main-img" id="pdMain">
          <img src="<?php h(upload_url($images[0], 'products', $p['name'])); ?>" alt="<?php h($p['name']); ?>" id="pdMainImg">
          <?php if ($disc > 0): ?><span class="badge badge-sale" style="position:absolute;top:16px;left:16px">-<?= $disc ?>%</span><?php endif; ?>
        </div>
        <div class="pd-thumbs">
          <?php foreach ($images as $i => $img): ?>
          <button type="button" class="pd-thumb <?= $i === 0 ? 'active' : '' ?>" aria-label="Afficher la vue <?= $i + 1 ?>" onclick="pdSelect(this, '<?php h(upload_url($img, 'products', $p['name'] . ' ' . ($i+1))); ?>')">
            <img src="<?php h(upload_url($img, 'products', $p['name'] . ' ' . ($i+1))); ?>" alt="">
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Infos -->
      <div class="pd-info">
        <span class="pc-brand"><a href="<?php h(url('shop.php?brand=' . ($p['brand_slug'] ?? ''))); ?>"><?php h($p['brand_name'] ?? 'Bella Rosa'); ?></a></span>
        <h1><?php h($p['name']); ?></h1>
        <div class="pd-meta-row">
          <?= star_rating((float) $p['rating'], true) ?>
          <span>· <?= (int) $p['reviews_count'] ?> avis</span>
          <span>· Réf. <?php h($p['sku']); ?></span>
          <?php if ($out): ?><span class="badge badge-out">Épuisé</span>
          <?php else: ?><span style="color:var(--success);font-weight:600">● En stock</span><?php endif; ?>
        </div>

        <div class="pd-price">
          <span class="now"><?= money($p['price']) ?></span>
          <?php if ($p['old_price'] > $p['price']): ?>
            <span class="old"><?= money($p['old_price']) ?></span>
            <span class="badge badge-sale">Économisez <?= money($p['old_price'] - $p['price']) ?></span>
          <?php endif; ?>
        </div>

        <p class="pd-desc"><?php h($p['short_desc']); ?></p>

        <div class="pd-buy">
          <div class="qty">
            <button type="button" onclick="pdQty(-1)"><?= icon('minus') ?></button>
            <input type="number" id="pdQtyInput" value="1" min="1" max="<?= max(1,(int)$p['stock']) ?>" readonly>
            <button type="button" onclick="pdQty(1)"><?= icon('plus') ?></button>
          </div>
          <?php if ($out): ?>
            <button class="btn btn-lg" disabled style="opacity:.6;flex:1">Produit épuisé</button>
          <?php else: ?>
            <button class="btn btn-lg" data-id="<?= (int) $p['id'] ?>" id="pdAddBtn" style="flex:1"><?= icon('cart') ?> Ajouter au panier</button>
          <?php endif; ?>
          <button class="pc-action wish-btn <?= $inWish ? 'active' : '' ?>" data-id="<?= (int) $p['id'] ?>" style="width:52px;height:52px" title="Favoris"><?= icon('heart') ?></button>
        </div>

        <div class="pd-features">
          <div class="pd-feature"><?= icon('truck') ?> <div><strong>Livraison rapide</strong><br>58 wilayas · <?= money(setting('shipping_flat',600)) ?></div></div>
          <div class="pd-feature"><?= icon('wallet') ?> <div><strong>Paiement livraison</strong><br>Payez à réception</div></div>
          <div class="pd-feature"><?= icon('shield') ?> <div><strong>100% authentique</strong><br>Produit garanti</div></div>
          <div class="pd-feature"><?= icon('refresh') ?> <div><strong>Retour 14 jours</strong><br>Satisfait ou remboursé</div></div>
        </div>

        <div class="chip" style="margin-top:18px"><?= icon('leaf') ?> Volume : <?php h($p['volume']); ?> · Poids : <?php h($p['weight']); ?> g</div>
      </div>
    </div>

    <!-- Onglets -->
    <div class="pd-tabs">
      <div class="tab-nav">
        <button class="active" data-tab="desc">Description</button>
        <button data-tab="ingredients">Composition</button>
        <button data-tab="usage">Conseils d'utilisation</button>
        <button data-tab="reviews">Avis (<?= count($reviews) ?>)</button>
      </div>
      <div class="tab-panel active" data-panel="desc">
        <p style="max-width:760px;color:var(--muted)"><?php h($p['long_desc']); ?></p>
        <table class="spec-table" style="max-width:520px;margin-top:20px">
          <tr><td>Marque</td><td><?php h($p['brand_name'] ?? 'Bella Rosa'); ?></td></tr>
          <tr><td>Catégorie</td><td><?php h($p['category_name'] ?? '—'); ?></td></tr>
          <tr><td>Référence</td><td><?php h($p['sku']); ?></td></tr>
          <tr><td>Volume</td><td><?php h($p['volume']); ?></td></tr>
          <tr><td>Poids</td><td><?php h($p['weight']); ?> g</td></tr>
        </table>
      </div>
      <div class="tab-panel" data-panel="ingredients">
        <p style="max-width:760px;color:var(--muted)"><strong>INCI :</strong> <?php h($p['ingredients']); ?></p>
      </div>
      <div class="tab-panel" data-panel="usage">
        <p style="max-width:760px;color:var(--muted)"><?php h($p['usage_tips']); ?></p>
      </div>
      <div class="tab-panel" data-panel="reviews">
        <div style="max-width:760px">
          <?php if (empty($reviews)): ?>
            <p style="color:var(--muted)">Aucun avis pour le moment. Soyez la première à donner votre avis !</p>
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
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <div class="form-row">
              <div class="field"><label>Votre nom</label><input name="author_name" required value="<?= e(current_user()['first_name'] ?? '') ?>"></div>
              <div class="field"><label>Note</label>
                <select name="rating" class="select" style="width:100%">
                  <option value="5">5 étoiles — Excellent</option><option value="4">4 étoiles — Très bien</option>
                  <option value="3">3 étoiles — Bien</option><option value="2">2 étoiles — Moyen</option><option value="1">1 étoile — Décevant</option>
                </select>
              </div>
            </div>
            <div class="field"><label>Titre</label><input name="title" placeholder="Résumez votre expérience"></div>
            <div class="field"><label>Votre avis</label><textarea name="body" required placeholder="Partagez votre expérience avec ce produit…"></textarea></div>
            <button class="btn">Publier mon avis</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Produits similaires -->
    <?php if ($related): ?>
    <div style="margin-top:64px">
      <?= section_head('Vous aimerez aussi', 'Produits similaires') ?>
      <div class="product-grid"><?php foreach ($related as $rp) { echo product_card($rp); } ?></div>
    </div>
    <?php endif; ?>

    <!-- Récemment vus -->
    <?php if ($recent): ?>
    <div style="margin-top:56px">
      <?= section_head('Votre historique', 'Récemment consultés') ?>
      <div class="product-grid"><?php foreach (array_slice($recent, 0, 4) as $rp) { echo product_card($rp); } ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
function pdSelect(el, src){ document.querySelectorAll('.pd-thumb').forEach(t=>t.classList.remove('active')); el.classList.add('active'); document.getElementById('pdMainImg').src = src; }
function pdQty(d){ const i=document.getElementById('pdQtyInput'); let v=parseInt(i.value)+d; const max=parseInt(i.max)||99; i.value=Math.max(1,Math.min(max,v)); }
document.querySelectorAll('.tab-nav button').forEach(b=>b.addEventListener('click',()=>{
  document.querySelectorAll('.tab-nav button').forEach(x=>x.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(x=>x.classList.remove('active'));
  b.classList.add('active');
  document.querySelector('[data-panel="'+b.dataset.tab+'"]').classList.add('active');
}));
// zoom léger au survol de l'image principale
const main=document.getElementById('pdMain');
if(main){ const img=document.getElementById('pdMainImg');
  main.addEventListener('mousemove',e=>{const r=main.getBoundingClientRect();img.style.transformOrigin=((e.clientX-r.left)/r.width*100)+'% '+((e.clientY-r.top)/r.height*100)+'%';img.style.transform='scale(1.7)';});
  main.addEventListener('mouseleave',()=>{img.style.transform='';});
}
// bouton d'ajout avec quantité (id dédié, hors délégation globale)
document.getElementById('pdAddBtn')?.addEventListener('click',function(){
  Bella.addToCart(parseInt(this.dataset.id), parseInt(document.getElementById('pdQtyInput').value)||1, this);
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
