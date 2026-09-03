<?php
/**
 * lire.php - Lecteur en ligne.
 *   · Livres PDF  → visionneuse PDF native (iframe) alimentée par book-pdf.php
 *   · Présentations PowerPoint → lecteur de diapositives HTML (includes/pptx.php)
 *
 * Sans achat, seules les N premières pages / diapositives sont accessibles.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/icons.php';

$book = book_by_slug(input('slug'));
if (!$book) { redirect('catalogue.php'); }

$owned    = has_book_access((int) $book['id']);
$isSlides = book_is_slides($book);
$unit     = book_unit($book);
$previewN = max(1, (int) $book['preview_pages']);
$totalN   = (int) $book['pages_count'];

$slides = [];
$slideError = '';
if ($isSlides) {
    require_once INCLUDES_PATH . '/pptx.php';
    $path = UPLOADS_PATH . '/pdf/' . basename((string) $book['pdf_file']);
    if (!pptx_is_supported()) {
        $slideError = 'La lecture des présentations nécessite l\'extension PHP « zip » sur le serveur.';
    } elseif (!is_file($path)) {
        $slideError = 'Le fichier de cette présentation est introuvable.';
    } else {
        $slides = pptx_slides($path, $owned ? 0 : $previewN);
        if (!$slides) { $slideError = 'Cette présentation n\'a pas pu être ouverte.'; }
        if ($totalN <= 0) { $totalN = pptx_slide_count($path); }
    }
} else {
    $pdfUrl = url('book-pdf.php?slug=' . urlencode($book['slug']) . '&mode=' . ($owned ? 'full' : 'preview'));
}
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lecture · <?php h($book['title']); ?></title>
<link rel="icon" href="<?php h(asset('images/favicon.svg')); ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php h(asset('css/style.css')); ?>">
<script>(function(){try{var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
</head>
<body>
<div class="reader">
  <div class="reader-bar">
    <a href="<?php h(url('livre.php?slug=' . $book['slug'])); ?>" class="icon-btn" style="color:#e6d6b8" title="Retour"><?= icon('arrow','flip') ?></a>
    <span class="r-title"><?php h($book['title']); ?></span>
    <span class="chip r-format"><?= $isSlides ? icon('presentation') : icon('pages') ?> <?= e(book_format_label($book)) ?></span>
    <span class="spacer"></span>
    <?php if ($owned): ?>
      <span class="chip" style="background:rgba(79,125,63,.2);color:#bfe0a8;border-color:transparent"><?= icon('unlock') ?> Version complète</span>
      <?php if ($isSlides): ?>
        <a href="<?php h(url('book-pdf.php?slug=' . urlencode($book['slug']) . '&mode=full&download=1')); ?>" class="btn btn-sm btn-light" title="Télécharger le fichier PowerPoint"><?= icon('download') ?> .pptx</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="<?php h(url('livre.php?slug=' . $book['slug'])); ?>" class="btn btn-gold btn-sm"><?= icon('lock') ?> Acheter · <?= e(book_price_label($book)) ?></a>
    <?php endif; ?>
  </div>

  <?php if (!$owned): ?>
    <div class="reader-notice"><?= icon('eye') ?> Vous lisez l'aperçu gratuit, les <?= $previewN ?> premières <?= e($unit) ?>. Achetez le titre pour lire les <?= $totalN ?> <?= e($unit) ?>.</div>
  <?php endif; ?>

  <?php if (!$isSlides): ?>
    <div class="reader-stage">
      <iframe class="reader-frame" src="<?php h($pdfUrl); ?>#toolbar=1&navpanes=0" title="Lecteur"></iframe>
    </div>

  <?php elseif ($slideError): ?>
    <div class="reader-stage">
      <div class="slide-error">
        <?= icon('presentation') ?>
        <p><?php h($slideError); ?></p>
        <?php if ($owned): ?>
          <a href="<?php h(url('book-pdf.php?slug=' . urlencode($book['slug']) . '&mode=full&download=1')); ?>" class="btn"><?= icon('download') ?> Télécharger la présentation</a>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <div class="reader-stage slides-stage">
      <div class="deck" id="deck" data-total="<?= count($slides) ?>">
        <?php foreach ($slides as $i => $s): ?>
        <section class="slide<?= $i === 0 ? ' is-current' : '' ?>" data-index="<?= $i ?>" <?= $i === 0 ? '' : 'hidden' ?>>
          <div class="slide-inner">
            <span class="slide-no"><?= (int) $s['index'] ?></span>
            <?php if ($s['title'] !== ''): ?><h2 class="slide-title"><?php h($s['title']); ?></h2><?php endif; ?>

            <?php foreach ($s['blocks'] as $block): ?>
              <ul class="slide-list">
                <?php foreach ($block as $para): ?>
                  <li class="lvl-<?= (int) $para['level'] ?><?= $para['bullet'] ? '' : ' no-bullet' ?>"><?php h($para['text']); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endforeach; ?>

            <?php foreach ($s['tables'] as $table): ?>
              <div class="slide-table-wrap">
                <table class="slide-table">
                  <?php foreach ($table as $r => $row): ?>
                    <tr>
                      <?php foreach ($row as $cell): ?>
                        <?php if ($r === 0): ?><th><?php h($cell); ?></th><?php else: ?><td><?php h($cell); ?></td><?php endif; ?>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </table>
              </div>
            <?php endforeach; ?>

            <?php if ($s['images'] > 0): ?>
              <div class="slide-figures">
                <?php for ($k = 0; $k < $s['images']; $k++): ?>
                  <img src="<?php h(url('book-slide.php?slug=' . urlencode($book['slug']) . '&slide=' . (int) $s['index'] . '&i=' . $k)); ?>"
                       alt="Illustration <?= $k + 1 ?> de la diapositive <?= (int) $s['index'] ?>" loading="lazy">
                <?php endfor; ?>
              </div>
            <?php endif; ?>

            <?php if ($s['title'] === '' && !$s['blocks'] && !$s['tables'] && $s['images'] === 0): ?>
              <p class="slide-empty">Diapositive sans contenu textuel.</p>
            <?php endif; ?>
          </div>

          <?php if ($s['notes'] !== ''): ?>
            <details class="slide-notes">
              <summary><?= icon('edit') ?> Notes du présentateur</summary>
              <p><?php h($s['notes']); ?></p>
            </details>
          <?php endif; ?>
        </section>
        <?php endforeach; ?>
      </div>

      <div class="deck-controls">
        <button class="btn btn-outline btn-sm" id="deckPrev" type="button"><?= icon('arrow','flip') ?> Précédente</button>
        <span class="deck-counter"><span id="deckPos">1</span> / <?= count($slides) ?><?= $owned ? '' : ' (aperçu)' ?></span>
        <button class="btn btn-sm" id="deckNext" type="button">Suivante <?= icon('arrow') ?></button>
      </div>

      <div class="deck-dots" id="deckDots" role="tablist" aria-label="Diapositives">
        <?php foreach ($slides as $i => $s): ?>
          <button type="button" class="deck-dot<?= $i === 0 ? ' is-active' : '' ?>" data-go="<?= $i ?>"
                  role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                  aria-label="Diapositive <?= (int) $s['index'] ?>"><?= (int) $s['index'] ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$owned): ?>
  <div style="text-align:center;padding:22px">
    <a href="<?php h(url('livre.php?slug=' . $book['slug'])); ?>" class="btn btn-lg"><?= icon('book') ?> Débloquer la version complète · <?= e(book_price_label($book)) ?></a>
  </div>
  <?php endif; ?>
</div>

<?php if ($isSlides && $slides): ?>
<script>
(function () {
  var deck  = document.getElementById('deck');
  if (!deck) { return; }
  var items = Array.prototype.slice.call(deck.querySelectorAll('.slide'));
  var dots  = Array.prototype.slice.call(document.querySelectorAll('.deck-dot'));
  var pos   = document.getElementById('deckPos');
  var prev  = document.getElementById('deckPrev');
  var next  = document.getElementById('deckNext');
  var cur   = 0;

  function show(i) {
    cur = Math.max(0, Math.min(items.length - 1, i));
    items.forEach(function (el, k) {
      el.hidden = k !== cur;
      el.classList.toggle('is-current', k === cur);
    });
    dots.forEach(function (d, k) {
      d.classList.toggle('is-active', k === cur);
      d.setAttribute('aria-selected', k === cur ? 'true' : 'false');
    });
    if (pos) { pos.textContent = String(cur + 1); }
    if (prev) { prev.disabled = cur === 0; }
    if (next) { next.disabled = cur === items.length - 1; }
    if (dots[cur] && dots[cur].scrollIntoView) {
      dots[cur].scrollIntoView({ block: 'nearest', inline: 'center' });
    }
  }

  if (prev) { prev.addEventListener('click', function () { show(cur - 1); }); }
  if (next) { next.addEventListener('click', function () { show(cur + 1); }); }
  dots.forEach(function (d) {
    d.addEventListener('click', function () { show(parseInt(d.dataset.go, 10) || 0); });
  });

  document.addEventListener('keydown', function (e) {
    if (e.target && /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) { return; }
    if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); show(cur + 1); }
    else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); show(cur - 1); }
    else if (e.key === 'Home') { e.preventDefault(); show(0); }
    else if (e.key === 'End') { e.preventDefault(); show(items.length - 1); }
  });

  // Balayage tactile
  var x0 = null;
  deck.addEventListener('touchstart', function (e) { x0 = e.changedTouches[0].clientX; }, { passive: true });
  deck.addEventListener('touchend', function (e) {
    if (x0 === null) { return; }
    var dx = e.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 60) { show(dx < 0 ? cur + 1 : cur - 1); }
    x0 = null;
  }, { passive: true });

  show(0);
})();
</script>
<?php endif; ?>
</body>
</html>
