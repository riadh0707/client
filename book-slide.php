<?php
/**
 * book-slide.php - Diffusion contrôlée des images d'une présentation PowerPoint.
 *   ?slug=<slug>&slide=<n>&i=<k>   → k-ième image de la diapositive n
 *
 * Les images des diapositives au-delà de l'aperçu gratuit ne sont servies
 * qu'aux acheteurs : le fichier .pptx lui-même reste inaccessible en direct
 * (uploads/pdf/.htaccess), tout passe par ce point contrôlé.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';
require_once INCLUDES_PATH . '/pptx.php';

$book = book_by_slug(trim((string) input('slug')));
if (!$book || !book_is_slides($book)) { http_response_code(404); exit('Introuvable.'); }

$slideNo = max(1, (int) input('slide'));
$index   = max(0, (int) input('i'));

if (!has_book_access((int) $book['id']) && $slideNo > max(1, (int) $book['preview_pages'])) {
    http_response_code(403);
    exit('Accès refusé : diapositive hors de l\'aperçu gratuit.');
}

$path  = UPLOADS_PATH . '/pdf/' . basename((string) $book['pdf_file']);
$image = pptx_slide_image($path, $slideNo, $index);
if (!$image) { http_response_code(404); exit('Image introuvable.'); }

header('Content-Type: ' . $image['mime']);
header('Content-Length: ' . strlen($image['data']));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
echo $image['data'];
exit;
