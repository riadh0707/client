<?php
/**
 * placeholder.php — Générateur d'images SVG élégantes à la volée.
 *
 * Fournit des visuels premium (dégradés doux, monogramme, motif) sans aucune
 * image binaire ni requête externe : idéal pour la démo et pour l'hébergement
 * AwardSpace Free (poids et bande passante minimes). Mise en cache navigateur.
 *
 *   ?t=products&s=<seed>&l=<label>   type / graine couleur / libellé
 */

$type  = preg_replace('/[^a-z]/', '', strtolower($_GET['t'] ?? 'products'));
$seed  = (string) ($_GET['s'] ?? 'bella');
$label = mb_substr((string) ($_GET['l'] ?? ''), 0, 26);

// Palettes douces et féminines par type de contenu.
$palettes = [
    'products'   => [['#f6e7e4', '#e9c4c1'], ['#f3e8ef', '#d9b8d3'], ['#f8ede2', '#e7c9a9'], ['#eef0ea', '#cdd6c3'], ['#fdeef0', '#f2c9d3']],
    'categories' => [['#b76e79', '#e2a0a8'], ['#c9a24b', '#e6cf93'], ['#9c6b8e', '#c8a0bd'], ['#7a9b8e', '#aecabd']],
    'banners'    => [['#b76e79', '#c9a24b'], ['#9c6b8e', '#b76e79'], ['#33222a', '#7a5560']],
    'blog'       => [['#efe6f1', '#cbb3d6'], ['#e7eef0', '#b0cdd6'], ['#f5ece4', '#e0c3a0']],
    'brands'     => [['#faf6f4', '#e7d7d2']],
    'avatars'    => [['#b76e79', '#e2a0a8'], ['#c9a24b', '#e6cf93'], ['#9c6b8e', '#c8a0bd']],
];
$set = $palettes[$type] ?? $palettes['products'];
$idx = abs(crc32($seed)) % count($set);
[$c1, $c2] = $set[$idx];

// Monogramme.
$mono = '';
foreach (preg_split('/\s+/', trim($label ?: $seed)) as $w) {
    if ($w !== '') { $mono .= mb_strtoupper(mb_substr($w, 0, 1)); }
    if (mb_strlen($mono) >= 2) { break; }
}
$mono = $mono ?: 'B';

$w = 800; $h = ($type === 'banners') ? 500 : 800;
$textColor = ($type === 'categories' || $type === 'banners' || $type === 'avatars') ? '#ffffff' : '#b76e79';
$rot = (abs(crc32($seed . 'r')) % 40) - 20;

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=2592000');
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $w ?> <?= $h ?>" width="<?= $w ?>" height="<?= $h ?>">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="<?= $c1 ?>"/>
      <stop offset="1" stop-color="<?= $c2 ?>"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.7" cy="0.25" r="0.8">
      <stop offset="0" stop-color="#ffffff" stop-opacity="0.35"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="<?= $w ?>" height="<?= $h ?>" fill="url(#g)"/>
  <rect width="<?= $w ?>" height="<?= $h ?>" fill="url(#glow)"/>
  <!-- pétales décoratifs -->
  <g fill="#ffffff" opacity="0.12" transform="rotate(<?= $rot ?> <?= $w/2 ?> <?= $h/2 ?>)">
    <ellipse cx="<?= $w*0.5 ?>" cy="<?= $h*0.5 ?>" rx="150" ry="60"/>
    <ellipse cx="<?= $w*0.5 ?>" cy="<?= $h*0.5 ?>" rx="60" ry="150"/>
    <ellipse cx="<?= $w*0.5 ?>" cy="<?= $h*0.5 ?>" rx="120" ry="120" opacity="0.5"/>
  </g>
  <circle cx="<?= $w*0.5 ?>" cy="<?= $h*0.44 ?>" r="88" fill="#ffffff" opacity="0.55"/>
  <text x="50%" y="<?= $h*0.44 ?>" dy="0.35em" text-anchor="middle"
        font-family="Georgia, 'Times New Roman', serif" font-size="86" font-weight="600"
        fill="<?= $textColor ?>"><?= htmlspecialchars($mono, ENT_QUOTES) ?></text>
  <?php if ($label): ?>
  <text x="50%" y="<?= $h*0.68 ?>" text-anchor="middle"
        font-family="system-ui, sans-serif" font-size="30" letter-spacing="1"
        fill="<?= $textColor ?>" opacity="0.85"><?= htmlspecialchars($label, ENT_QUOTES) ?></text>
  <?php endif; ?>
</svg>
