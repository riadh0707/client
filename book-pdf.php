<?php
/**
 * book-pdf.php - Diffusion contrôlée du fichier d'un titre (PDF ou PowerPoint).
 *   ?slug=<slug>&mode=preview   → PDF d'aperçu (N premières pages) - public
 *   ?slug=<slug>&mode=full      → fichier complet - réservé aux acheteurs
 *   &download=1                 → force le téléchargement plutôt que l'affichage
 *
 * Le fichier est lu côté serveur puis diffusé. Les fichiers complets ne sont
 * jamais accessibles en direct (voir uploads/pdf/.htaccess).
 *
 * Les présentations PowerPoint n'ont pas d'aperçu « fichier » : leur aperçu est
 * rendu en HTML par lire.php, qui ne charge que les N premières diapositives.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/queries.php';

$slug = trim((string) input('slug'));
$mode = input('mode', 'preview') === 'full' ? 'full' : 'preview';
$book = book_by_slug($slug);
if (!$book) { http_response_code(404); exit('Titre introuvable.'); }

$isSlides = book_is_slides($book);
$fileName = basename((string) $book['pdf_file']);

if ($mode === 'full') {
    if (!has_book_access((int) $book['id'])) {
        http_response_code(403);
        exit('Accès refusé : ce titre n\'a pas été acheté.');
    }
    $path = UPLOADS_PATH . '/pdf/' . $fileName;
} elseif ($isSlides) {
    // Aperçu d'une présentation : passe par le lecteur de diapositives.
    redirect('lire.php?slug=' . urlencode($book['slug']));
} else {
    // Aperçu : le PDF d'aperçu (N pages) s'il existe, sinon on le génère.
    // On ne diffuse jamais le complet en aperçu s'il n'est pas préparé.
    $preview = $book['preview_file'] ? UPLOADS_PATH . '/previews/' . basename((string) $book['preview_file']) : '';
    if ($preview && is_file($preview)) {
        $path = $preview;
    } else {
        require_once INCLUDES_PATH . '/pdf.php';
        $full = UPLOADS_PATH . '/pdf/' . $fileName;
        $gen  = UPLOADS_PATH . '/previews/' . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
        if (is_file($full) && pdf_make_preview($full, $gen, (int) $book['preview_pages'])) {
            $path = $gen;
        } else {
            http_response_code(404); exit('Aperçu indisponible.');
        }
    }
}

if (!is_file($path)) { http_response_code(404); exit('Fichier introuvable.'); }

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = [
    'pdf'  => 'application/pdf',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'ppt'  => 'application/vnd.ms-powerpoint',
];
$mime = $types[$ext] ?? 'application/octet-stream';

// Les formats Office ne s'affichent pas dans le navigateur : toujours en pièce jointe.
$forceDownload = input('download') === '1' || $ext !== 'pdf';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($forceDownload ? 'attachment' : 'inline')
    . '; filename="' . slugify($book['title']) . '.' . $ext . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
