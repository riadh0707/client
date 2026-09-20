<?php
/**
 * captcha.php - Image SVG du captcha intégré (code de sécurité anti-robot).
 * Génère un nouveau code, le mémorise en session et renvoie une image SVG.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/captcha.php';

$code = captcha_new_code();

if (($GLOBALS['config']['app']['env'] ?? '') === 'development') { header('X-Captcha-Debug: ' . $code); }

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$w = 150; $h = 52;
$colors = ['#4a3220', '#6b4726', '#8a5a34', '#a3302a', '#7a4a2a'];
mt_srand(crc32($code . microtime()));
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $w ?> <?= $h ?>" width="<?= $w ?>" height="<?= $h ?>">
  <rect width="<?= $w ?>" height="<?= $h ?>" fill="#f3ecdc" rx="8"/>
  <?php for ($i = 0; $i < 5; $i++): // lignes de bruit
      $x1 = mt_rand(0, $w); $y1 = mt_rand(0, $h); $x2 = mt_rand(0, $w); $y2 = mt_rand(0, $h); ?>
    <line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" stroke="<?= $colors[array_rand($colors)] ?>" stroke-width="1" opacity="0.35"/>
  <?php endfor; ?>
  <?php for ($i = 0; $i < 18; $i++): // points de bruit ?>
    <circle cx="<?= mt_rand(0, $w) ?>" cy="<?= mt_rand(0, $h) ?>" r="<?= mt_rand(1, 2) ?>" fill="<?= $colors[array_rand($colors)] ?>" opacity="0.3"/>
  <?php endfor; ?>
  <?php $len = strlen($code); for ($i = 0; $i < $len; $i++):
      $x = 18 + $i * 26; $y = 34 + mt_rand(-4, 4); $rot = mt_rand(-22, 22); ?>
    <text x="<?= $x ?>" y="<?= $y ?>" font-family="Georgia, 'Times New Roman', serif" font-size="28" font-weight="700"
          fill="<?= $colors[array_rand($colors)] ?>" transform="rotate(<?= $rot ?> <?= $x ?> <?= $y ?>)"><?= htmlspecialchars($code[$i], ENT_QUOTES) ?></text>
  <?php endfor; ?>
</svg>
