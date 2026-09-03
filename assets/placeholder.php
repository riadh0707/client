<?php
/**
 * placeholder.php - Générateur de visuels SVG « vintage » à la volée.
 * Couvertures de livres, catégories, bannières, avatars - aucun binaire,
 * aucune requête externe (idéal AwardSpace). Mise en cache navigateur.
 *
 *   ?t=covers&s=<seed>&l=<titre>
 */

$type  = preg_replace('/[^a-z]/', '', strtolower($_GET['t'] ?? 'covers'));
$seed  = (string) ($_GET['s'] ?? 'livre');
$label = mb_substr((string) ($_GET['l'] ?? ''), 0, 46);

$palettes = [
    'covers'     => [['#4a3220', '#2c1c10'], ['#6b4726', '#3a2716'], ['#7a4a2a', '#432816'], ['#5a3d24', '#2e2013'], ['#864f2b', '#4a2e18']],
    'categories' => [['#6b4726', '#8a5a34'], ['#8a5a34', '#a3302a'], ['#4a3220', '#6b4726'], ['#a8863f', '#8a5a34']],
    'banners'    => [['#4a3220', '#8a5a34'], ['#2c1c10', '#6b4726']],
    'blog'       => [['#e3d7bf', '#c9b48a'], ['#ece3d1', '#d8c093'], ['#f0e6cf', '#cbb083']],
    'avatars'    => [['#6b4726', '#a8863f'], ['#8a5a34', '#c9a959'], ['#4a3220', '#8a5a34']],
];
$set = $palettes[$type] ?? $palettes['covers'];
$idx = abs(crc32($seed)) % count($set);
[$c1, $c2] = $set[$idx];

$isCover = ($type === 'covers');
$w = 600; $h = $isCover ? 840 : ($type === 'banners' ? 380 : 400);

// Découpe le libellé en lignes d'au plus $max caractères.
$wrap = static function (string $text, int $max, int $maxLines): array {
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = []; $cur = '';
    foreach ($words as $word) {
        if ($cur !== '' && mb_strlen($cur . ' ' . $word) > $max) { $lines[] = $cur; $cur = $word; }
        else { $cur = $cur === '' ? $word : "$cur $word"; }
    }
    if ($cur !== '') { $lines[] = $cur; }
    return array_slice($lines, 0, $maxLines);
};

// Titre sur plusieurs lignes (pour la couverture).
$lines = $wrap($label, 16, 4);

// Libellé des vignettes (rayons, bannières, carnet) : plusieurs lignes + taille
// adaptée, pour qu'un nom long comme « Business & Entrepreneuriat » tienne dans
// le cadre au lieu de déborder.
$labelLines = $wrap($label, 18, 3);
$labelSize  = 60;
foreach ($labelLines as $l) {
    // ~0.55 em de large par caractère en Georgia : on réduit jusqu'à tenir.
    $labelSize = min($labelSize, (int) floor(($w - 80) / max(1, mb_strlen($l)) / 0.55));
}
$labelSize = max(22, min(60, $labelSize));

$mono = mb_strtoupper(mb_substr($seed, 0, 1)) ?: 'B';
$textColor = ($type === 'blog') ? '#4a3220' : '#f2e6cf';
$gold = '#c9a959';

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=2592000');
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $w ?> <?= $h ?>" width="<?= $w ?>" height="<?= $h ?>">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="<?= $c1 ?>"/><stop offset="1" stop-color="<?= $c2 ?>"/>
    </linearGradient>
    <filter id="grain"><feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2"/><feColorMatrix type="saturate" values="0"/><feComponentTransfer><feFuncA type="linear" slope="0.06"/></feComponentTransfer></filter>
  </defs>
  <rect width="<?= $w ?>" height="<?= $h ?>" fill="url(#g)"/>
  <rect width="<?= $w ?>" height="<?= $h ?>" filter="url(#grain)"/>
  <?php if ($isCover): ?>
    <!-- Dos du livre -->
    <rect x="0" y="0" width="26" height="<?= $h ?>" fill="#000" opacity="0.22"/>
    <rect x="26" y="0" width="4" height="<?= $h ?>" fill="#fff" opacity="0.08"/>
    <!-- Cadre orné -->
    <rect x="42" y="46" width="<?= $w-84 ?>" height="<?= $h-92 ?>" fill="none" stroke="<?= $gold ?>" stroke-width="2" opacity="0.7"/>
    <rect x="52" y="56" width="<?= $w-104 ?>" height="<?= $h-112 ?>" fill="none" stroke="<?= $gold ?>" stroke-width="1" opacity="0.5"/>
    <!-- Ornement haut -->
    <g fill="<?= $gold ?>" opacity="0.85">
      <path d="M<?= $w/2 ?> 120 l14 10 -14 8 -14 -8 z"/>
      <circle cx="<?= $w/2 - 40 ?>" cy="130" r="4"/><circle cx="<?= $w/2 + 40 ?>" cy="130" r="4"/>
      <rect x="<?= $w/2 - 70 ?>" y="128" width="30" height="2"/><rect x="<?= $w/2 + 40 ?>" y="128" width="30" height="2"/>
    </g>
    <!-- Titre -->
    <?php $ty = $h/2 - (count($lines) * 30) + 30; foreach ($lines as $i => $line): ?>
      <text x="50%" y="<?= $ty + $i*58 ?>" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-size="42" font-weight="700" fill="<?= $textColor ?>"><?= htmlspecialchars($line, ENT_QUOTES) ?></text>
    <?php endforeach; ?>
    <!-- Ornement bas + mention -->
    <g fill="<?= $gold ?>" opacity="0.8"><path d="M<?= $w/2 ?> <?= $h-150 ?> l14 -10 -14 -8 -14 8 z"/></g>
    <text x="50%" y="<?= $h-110 ?>" text-anchor="middle" font-family="Georgia, serif" font-size="22" fill="<?= $gold ?>" letter-spacing="4">BIBLIOTHÈQUE</text>
  <?php else: ?>
    <?php if ($type === 'avatars' || !$labelLines): ?>
      <text x="50%" y="50%" dy="0.35em" text-anchor="middle" font-family="Georgia, serif" font-size="<?= $type === 'avatars' ? 90 : 60 ?>" font-weight="700" fill="<?= $textColor ?>"><?= htmlspecialchars($type === 'avatars' ? $mono : ($label ?: $mono), ENT_QUOTES) ?></text>
    <?php else: $lh = (int) round($labelSize * 1.22); $ly = (int) round($h / 2 - (count($labelLines) - 1) * $lh / 2); ?>
      <?php foreach ($labelLines as $i => $line): ?>
        <text x="50%" y="<?= $ly + $i * $lh ?>" dy="0.35em" text-anchor="middle" font-family="Georgia, serif" font-size="<?= $labelSize ?>" font-weight="700" fill="<?= $textColor ?>"><?= htmlspecialchars($line, ENT_QUOTES) ?></text>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</svg>
