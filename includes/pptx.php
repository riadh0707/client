<?php
/**
 * pptx.php - Lecture des présentations PowerPoint (.pptx) en PHP pur.
 *
 * Un .pptx est une archive ZIP de fichiers XML (Office Open XML). On l'ouvre
 * avec ZipArchive (disponible partout, AwardSpace inclus) et on en extrait :
 *
 *   - pptx_slide_count()  : nombre de diapositives.
 *   - pptx_slides()       : titre, paragraphes (avec niveaux), tableaux, notes
 *                           et images de chaque diapositive.
 *   - pptx_slide_image()  : le binaire d'une image d'une diapositive, pour la
 *                           diffuser via un point d'entrée contrôlé.
 *
 * Aucune conversion externe (LibreOffice, API) n'est nécessaire : le lecteur
 * du site restitue les diapositives en HTML. Si le fichier est illisible, les
 * fonctions renvoient 0 / [] / null - l'appelant gère le repli.
 */

const PPTX_NS_A = 'http://schemas.openxmlformats.org/drawingml/2006/main';
const PPTX_NS_P = 'http://schemas.openxmlformats.org/presentationml/2006/main';
const PPTX_NS_R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

function pptx_is_supported(): bool
{
    return class_exists('ZipArchive');
}

/** Ouvre l'archive, ou null si le fichier n'est pas un .pptx exploitable. */
function pptx_open(string $path): ?ZipArchive
{
    if (!pptx_is_supported() || !is_file($path)) { return null; }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) { return null; }
    if ($zip->locateName('ppt/presentation.xml') === false) { $zip->close(); return null; }
    return $zip;
}

/**
 * Noms des diapositives dans l'ordre de la présentation.
 * L'ordre officiel est celui de ppt/_rels/presentation.xml.rels ; on retombe
 * sur un tri numérique de slideN.xml si les relations sont absentes.
 *
 * @return string[] ex. ['ppt/slides/slide1.xml', …]
 */
function pptx_slide_names(ZipArchive $zip): array
{
    $ordered = [];
    $presXml  = $zip->getFromName('ppt/presentation.xml');
    $relsXml  = $zip->getFromName('ppt/_rels/presentation.xml.rels');
    if ($presXml !== false && $relsXml !== false) {
        $rels = pptx_rels_map($relsXml, 'ppt');
        $doc = pptx_dom($presXml);
        if ($doc) {
            $xp = pptx_xpath($doc);
            foreach ($xp->query('//p:sldIdLst/p:sldId') as $node) {
                $rid = $node->getAttributeNS(PPTX_NS_R, 'id');
                if ($rid !== '' && isset($rels[$rid]) && $zip->locateName($rels[$rid]) !== false) {
                    $ordered[] = $rels[$rid];
                }
            }
        }
    }
    if ($ordered) { return $ordered; }

    // Repli : tri naturel de ppt/slides/slideN.xml
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) { $ordered[(int) $m[1]] = $name; }
    }
    ksort($ordered);
    return array_values($ordered);
}

/** Nombre de diapositives (0 si illisible). */
function pptx_slide_count(string $path): int
{
    $zip = pptx_open($path);
    if (!$zip) { return 0; }
    $n = count(pptx_slide_names($zip));
    $zip->close();
    return $n;
}

/**
 * Contenu des diapositives.
 *
 * @param int $limit 0 = toutes, sinon les $limit premières.
 * @return array<int, array{index:int,title:string,blocks:array,tables:array,images:int,notes:string}>
 */
function pptx_slides(string $path, int $limit = 0): array
{
    $zip = pptx_open($path);
    if (!$zip) { return []; }

    $names = pptx_slide_names($zip);
    if ($limit > 0) { $names = array_slice($names, 0, $limit); }

    $slides = [];
    foreach ($names as $i => $name) {
        $xml = $zip->getFromName($name);
        if ($xml === false) { continue; }
        $slide = pptx_parse_slide($xml);
        $slide['index']  = $i + 1;
        $slide['images'] = count(pptx_slide_image_targets($zip, $name));
        $slide['notes']  = pptx_slide_notes($zip, $name);
        $slides[] = $slide;
    }
    $zip->close();
    return $slides;
}

/**
 * Image n° $i (0-indexée) de la diapositive n° $slideNo (1-indexée).
 * @return array{mime:string,data:string}|null
 */
function pptx_slide_image(string $path, int $slideNo, int $i): ?array
{
    $zip = pptx_open($path);
    if (!$zip) { return null; }
    $names = pptx_slide_names($zip);
    $name  = $names[$slideNo - 1] ?? null;
    if ($name === null) { $zip->close(); return null; }

    $targets = pptx_slide_image_targets($zip, $name);
    $target  = $targets[$i] ?? null;
    if ($target === null) { $zip->close(); return null; }

    $data = $zip->getFromName($target);
    $zip->close();
    if ($data === false || $data === '') { return null; }

    $ext  = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    $mime = [
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'bmp' => 'image/bmp',  'webp' => 'image/webp',
        'svg' => 'image/svg+xml', 'tif' => 'image/tiff', 'tiff' => 'image/tiff',
        'emf' => 'image/emf', 'wmf' => 'image/wmf',
    ][$ext] ?? 'application/octet-stream';

    return ['mime' => $mime, 'data' => $data];
}

/* =========================================================================
 |  Interne
 * ========================================================================= */

function pptx_dom(string $xml): ?DOMDocument
{
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $ok = $doc->loadXML($xml, LIBXML_NONET | LIBXML_NOENT);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $ok ? $doc : null;
}

function pptx_xpath(DOMDocument $doc): DOMXPath
{
    $xp = new DOMXPath($doc);
    $xp->registerNamespace('a', PPTX_NS_A);
    $xp->registerNamespace('p', PPTX_NS_P);
    $xp->registerNamespace('r', PPTX_NS_R);
    return $xp;
}

/**
 * Relations d'une partie : ['rId1' => 'ppt/media/image1.png', …]
 * $baseDir est le dossier de la partie qui porte ces relations.
 */
function pptx_rels_map(string $xml, string $baseDir): array
{
    $doc = pptx_dom($xml);
    if (!$doc) { return []; }
    $map = [];
    foreach ($doc->getElementsByTagName('Relationship') as $rel) {
        if ($rel->getAttribute('TargetMode') === 'External') { continue; }
        $target = $rel->getAttribute('Target');
        if ($target === '') { continue; }
        $map[$rel->getAttribute('Id')] = pptx_resolve_path($baseDir, $target);
    }
    return $map;
}

/** Résout « ../media/image1.png » relativement au dossier de la partie. */
function pptx_resolve_path(string $baseDir, string $target): string
{
    if ($target[0] === '/') { return ltrim($target, '/'); }
    $parts = explode('/', trim($baseDir, '/'));
    foreach (explode('/', $target) as $seg) {
        if ($seg === '' || $seg === '.') { continue; }
        if ($seg === '..') { array_pop($parts); continue; }
        $parts[] = $seg;
    }
    return implode('/', $parts);
}

/** Chemins (dans le ZIP) des images d'une diapositive, dans l'ordre du document. */
function pptx_slide_image_targets(ZipArchive $zip, string $slideName): array
{
    $relsName = dirname($slideName) . '/_rels/' . basename($slideName) . '.rels';
    $relsXml  = $zip->getFromName($relsName);
    if ($relsXml === false) { return []; }
    $rels = pptx_rels_map($relsXml, dirname($slideName));

    $xml = $zip->getFromName($slideName);
    if ($xml === false) { return []; }
    $doc = pptx_dom($xml);
    if (!$doc) { return []; }

    $targets = [];
    foreach (pptx_xpath($doc)->query('//a:blip') as $blip) {
        $rid = $blip->getAttributeNS(PPTX_NS_R, 'embed');
        if ($rid === '') { $rid = $blip->getAttributeNS(PPTX_NS_R, 'link'); }
        if ($rid === '' || !isset($rels[$rid])) { continue; }
        if ($zip->locateName($rels[$rid]) === false) { continue; }
        $targets[] = $rels[$rid];
    }
    return $targets;
}

/** Notes du présentateur (texte brut) d'une diapositive. */
function pptx_slide_notes(ZipArchive $zip, string $slideName): string
{
    $relsName = dirname($slideName) . '/_rels/' . basename($slideName) . '.rels';
    $relsXml  = $zip->getFromName($relsName);
    if ($relsXml === false) { return ''; }

    $doc = pptx_dom($relsXml);
    if (!$doc) { return ''; }
    foreach ($doc->getElementsByTagName('Relationship') as $rel) {
        if (!str_ends_with($rel->getAttribute('Type'), '/notesSlide')) { continue; }
        $target = pptx_resolve_path(dirname($slideName), $rel->getAttribute('Target'));
        $xml = $zip->getFromName($target);
        if ($xml === false) { return ''; }
        $nd = pptx_dom($xml);
        if (!$nd) { return ''; }
        $lines = [];
        foreach (pptx_xpath($nd)->query('//a:p') as $p) {
            $t = pptx_paragraph_text($p);
            if ($t !== '') { $lines[] = $t; }
        }
        // La dernière ligne des notes est souvent le numéro de diapositive.
        if ($lines && preg_match('/^\d+$/', end($lines))) { array_pop($lines); }
        return trim(implode("\n", $lines));
    }
    return '';
}

/** Texte d'un paragraphe <a:p> (runs + sauts de ligne). */
function pptx_paragraph_text(DOMNode $p): string
{
    $out = '';
    foreach ($p->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) { continue; }
        if ($child->namespaceURI !== PPTX_NS_A) { continue; }
        if ($child->localName === 'r' || $child->localName === 'fld') {
            foreach ($child->childNodes as $sub) {
                if ($sub->nodeType === XML_ELEMENT_NODE && $sub->localName === 't') {
                    $out .= $sub->textContent;
                }
            }
        } elseif ($child->localName === 'br') {
            $out .= "\n";
        }
    }
    return trim(preg_replace('/[ \t]+/u', ' ', $out));
}

/** Analyse une diapositive : titre, blocs de texte, tableaux. */
function pptx_parse_slide(string $xml): array
{
    $slide = ['index' => 0, 'title' => '', 'blocks' => [], 'tables' => [], 'images' => 0, 'notes' => ''];
    $doc = pptx_dom($xml);
    if (!$doc) { return $slide; }
    $xp = pptx_xpath($doc);

    // --- Formes textuelles (dans l'ordre du document) ---
    foreach ($xp->query('//p:cSld/p:spTree//p:sp') as $sp) {
        $isTitle = false;
        foreach ($xp->query('.//p:nvSpPr/p:nvPr/p:ph', $sp) as $ph) {
            if (in_array($ph->getAttribute('type'), ['title', 'ctrTitle'], true)) { $isTitle = true; }
        }
        $paras = [];
        foreach ($xp->query('.//p:txBody/a:p', $sp) as $p) {
            $text = pptx_paragraph_text($p);
            if ($text === '') { continue; }
            $lvl = 0;
            $pPr = $xp->query('./a:pPr', $p)->item(0);
            if ($pPr instanceof DOMElement && $pPr->hasAttribute('lvl')) { $lvl = (int) $pPr->getAttribute('lvl'); }
            $bullet = $xp->query('./a:pPr/a:buNone', $p)->length === 0;
            $paras[] = ['level' => max(0, min(4, $lvl)), 'text' => $text, 'bullet' => $bullet];
        }
        if (!$paras) { continue; }

        if ($isTitle && $slide['title'] === '') {
            $slide['title'] = implode(' ', array_column($paras, 'text'));
            continue;
        }
        $slide['blocks'][] = $paras;
    }

    // --- Tableaux ---
    foreach ($xp->query('//p:cSld/p:spTree//a:tbl') as $tbl) {
        $rows = [];
        foreach ($xp->query('./a:tr', $tbl) as $tr) {
            $cells = [];
            foreach ($xp->query('./a:tc', $tr) as $tc) {
                $texts = [];
                foreach ($xp->query('.//a:txBody/a:p', $tc) as $p) {
                    $t = pptx_paragraph_text($p);
                    if ($t !== '') { $texts[] = $t; }
                }
                $cells[] = implode("\n", $texts);
            }
            if ($cells) { $rows[] = $cells; }
        }
        if ($rows) { $slide['tables'][] = $rows; }
    }

    // Pas de titre explicite : on promeut la première ligne du premier bloc.
    if ($slide['title'] === '' && $slide['blocks']) {
        $first = &$slide['blocks'][0];
        if (count($first) > 1 && mb_strlen($first[0]['text']) <= 90) {
            $slide['title'] = $first[0]['text'];
            array_shift($first);
            if (!$first) { array_shift($slide['blocks']); }
        }
        unset($first);
    }

    return $slide;
}
