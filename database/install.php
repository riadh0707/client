<?php
/**
 * install.php - Assistant d'installation (web ou CLI). MySQL ou SQLite.
 * ⚠️ Supprimez ce fichier après installation en production.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/seed_map.php';

$isCli = (PHP_SAPI === 'cli');

function out(string $msg, bool $cli): void
{
    echo $cli ? $msg . "\n" : '<div class="line">' . e($msg) . '</div>';
    if (!$cli) { @ob_flush(); @flush(); }
}

function run_install(bool $cli): void
{
    $driver = Database::driver();
    $pdo = Database::pdo();
    out("→ Driver : $driver", $cli);

    if ($driver === 'sqlite') {
        require __DIR__ . '/schema_sqlite.php';
        $tables = ['newsletter', 'wishlists', 'testimonials', 'blog_posts', 'coupons', 'book_access',
            'order_items', 'orders', 'reviews', 'customers', 'books', 'categories', 'admins', 'settings'];
        foreach ($tables as $t) { $pdo->exec("DROP TABLE IF EXISTS `$t`"); }
        foreach (sqlite_schema() as $stmt) { $pdo->exec($stmt); }
    } else {
        $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/schema_mysql.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '') { continue; }
            try { $pdo->exec($stmt); } catch (Throwable $e) {}
        }
    }
    out('✔ Schéma créé', $cli);

    $data = demo_data();
    $map = seed_map($data);
    $pdo->beginTransaction();
    foreach ($map as $table => $def) {
        if (empty($def['rows'])) { continue; }
        $cols = $def['columns'];
        $quoted = array_map(fn($c) => $driver === 'sqlite' ? $c : "`$c`", $cols);
        $ph = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
        $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(',', $quoted) . ') VALUES ' . $ph);
        foreach ($def['rows'] as $row) { $stmt->execute($row); }
        out("✔ $table : " . count($def['rows']) . ' lignes', $cli);
    }
    $pdo->commit();

    // Génère l'aperçu (N premières pages) du PDF exemple si absent.
    generate_sample_preview($cli);

    out('', $cli);
    out('✅ Installation terminée !', $cli);
    out('   Admin  : admin@bibliotheque-numerique.dz / admin123', $cli);
    out('   Client : client@bibliotheque-numerique.dz / client123', $cli);
}

/** Prépare l'aperçu du PDF exemple (via FPDI) pour la démo. */
function generate_sample_preview(bool $cli): void
{
    $full    = UPLOADS_PATH . '/pdf/livre-exemple.pdf';
    $preview = UPLOADS_PATH . '/previews/livre-exemple.pdf';
    if (!file_exists($full)) { out('ℹ PDF exemple absent (aperçu ignoré)', $cli); return; }
    if (file_exists($preview)) { return; }
    require_once ROOT_PATH . '/includes/pdf.php';
    if (pdf_make_preview($full, $preview, 10)) { out('✔ Aperçu PDF généré (10 pages)', $cli); }
    else { out('ℹ Aperçu non généré (le PDF complet servira d\'aperçu limité)', $cli); }
}

if ($isCli) { run_install(true); exit; }

$confirm = ($_GET['confirm'] ?? '') === '1';
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Installation · La Bibliothèque Numérique</title>
<style>
  body{font-family:system-ui,sans-serif;background:#ece3d1;color:#3a2716;margin:0;padding:40px 16px;line-height:1.6}
  .card{max-width:660px;margin:0 auto;background:#f8f2e4;border:1px solid #d8c9ac;border-radius:16px;padding:38px;box-shadow:0 20px 50px rgba(74,50,32,.16)}
  h1{font-size:24px;margin:0 0 6px;color:#6b4726}
  .btn{display:inline-block;background:linear-gradient(135deg,#8a5a34,#6b4726);color:#f8f2e4;text-decoration:none;padding:13px 26px;border-radius:999px;font-weight:600;margin-top:14px}
  code{background:#e3d7bf;padding:2px 7px;border-radius:5px;font-size:13px}
  .line{font-family:monospace;font-size:13px;padding:3px 0;border-bottom:1px solid #e3d7bf}
  .note{background:#f5e6c8;border:1px solid #e0c98a;border-radius:10px;padding:14px;font-size:14px;margin-top:18px;color:#7a5b1e}
</style></head><body><div class="card">
<h1>📖 Installation · La Bibliothèque Numérique</h1>
<?php if (!$confirm): ?>
  <p>Création des tables + données de démonstration (10 livres, clients, commandes, blog…).</p>
  <p>Driver : <code><?php h(Database::driver()); ?></code></p>
  <div class="note">⚠️ Les données existantes seront écrasées. Supprimez ce fichier après installation.</div>
  <a class="btn" href="?confirm=1">Lancer l'installation →</a>
<?php else: try { run_install(false); } catch (Throwable $e) { out('❌ ' . $e->getMessage(), false); } ?>
  <a class="btn" href="<?php h(url('index.php')); ?>">Voir le site →</a>
<?php endif; ?>
</div></body></html>
