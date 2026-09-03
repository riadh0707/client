<?php
/**
 * pdf.php — Outils PDF (pur PHP via FPDI + FPDF, bundlés dans /lib/vendor).
 *
 * - pdf_page_count()  : nombre de pages d'un PDF.
 * - pdf_make_preview(): génère un PDF d'aperçu = N premières pages.
 *
 * FPDI/FPDF fonctionnent sur hébergement mutualisé (AwardSpace). Si la librairie
 * n'est pas disponible ou si le PDF est protégé/incompatible, les fonctions
 * renvoient false / 0 sans casser l'application (repli géré par l'appelant).
 */

function pdf_lib_available(): bool
{
    return is_file(ROOT_PATH . '/lib/vendor/autoload.php');
}

function pdf_boot(): void
{
    static $done = false;
    if (!$done && pdf_lib_available()) {
        require_once ROOT_PATH . '/lib/vendor/autoload.php';
        $done = true;
    }
}

/** Nombre de pages, ou 0 si indéterminable. */
function pdf_page_count(string $src): int
{
    if (!is_file($src)) { return 0; }
    pdf_boot();
    if (class_exists('setasign\\Fpdi\\Fpdi')) {
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            return (int) $pdf->setSourceFile($src);
        } catch (Throwable $e) { /* repli ci-dessous */ }
    }
    // Repli grossier : compte les objets /Type /Page dans le flux.
    $data = @file_get_contents($src);
    if ($data === false) { return 0; }
    return max(0, (int) preg_match_all('/\/Type\s*\/Page[^s]/', $data));
}

/**
 * Génère $dest = les $n premières pages de $src.
 * Retourne true en cas de succès.
 */
function pdf_make_preview(string $src, string $dest, int $n = 10): bool
{
    if (!is_file($src)) { return false; }
    pdf_boot();
    if (!class_exists('setasign\\Fpdi\\Fpdi')) { return false; }
    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        $total = (int) $pdf->setSourceFile($src);
        $take  = max(1, min($n, $total));
        for ($i = 1; $i <= $take; $i++) {
            $tpl  = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);
        }
        @mkdir(dirname($dest), 0775, true);
        $pdf->Output('F', $dest);
        return is_file($dest);
    } catch (Throwable $e) {
        return false;
    }
}
