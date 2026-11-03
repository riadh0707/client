<?php
/**
 * install.php — Assistant d'installation (web ou CLI).
 *
 *   • MySQL  : crée les tables depuis schema_mysql.sql puis insère les données.
 *   • SQLite : crée un fichier local database/bella_rosa.sqlite prêt à l'emploi.
 *
 * Utilisation :
 *   - Navigateur : ouvrez  /database/install.php  puis suivez l'assistant.
 *   - CLI        : php database/install.php
 *
 * ⚠️ Supprimez ce fichier (ou protégez-le) après installation en production.
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
    $pdo    = Database::pdo();

    out("→ Driver : $driver", $cli);

    // 1) Schéma -----------------------------------------------------------
    if ($driver === 'sqlite') {
        require __DIR__ . '/schema_sqlite.php';
        // Réinitialisation : on supprime les tables existantes.
        $tables = ['newsletter', 'wishlists', 'testimonials', 'blog_posts', 'coupons',
            'order_items', 'orders', 'reviews', 'addresses', 'customers',
            'product_images', 'products', 'categories', 'brands', 'admins', 'settings'];
        foreach ($tables as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
        }
        foreach (sqlite_schema() as $stmt) {
            $pdo->exec($stmt);
        }
    } else {
        $sql = file_get_contents(__DIR__ . '/schema_mysql.sql');
        // Retrait des commentaires ligne puis exécution instruction par instruction.
        $sql = preg_replace('/^--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt === '') { continue; }
            try { $pdo->exec($stmt); } catch (Throwable $e) { /* SET no-op sur certains hébergeurs */ }
        }
    }
    out('✔ Schéma créé', $cli);

    // 2) Données ----------------------------------------------------------
    $data = demo_data();
    $map  = seed_map($data);

    $pdo->beginTransaction();
    foreach ($map as $table => $def) {
        if (empty($def['rows'])) { continue; }
        $cols = $def['columns'];
        $quoted = array_map(fn($c) => $driver === 'sqlite' ? $c : "`$c`", $cols);
        $ph = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
        $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(',', $quoted) . ') VALUES ' . $ph);
        foreach ($def['rows'] as $row) {
            $stmt->execute($row);
        }
        out("✔ $table : " . count($def['rows']) . ' lignes', $cli);
    }
    $pdo->commit();

    out('', $cli);
    out('✅ Installation terminée avec succès !', $cli);
    out('   Admin  : admin@bella-rosa.dz / admin123', $cli);
    out('   Client : client@bella-rosa.dz / client123', $cli);
}

/* ------------------------------------------------------------------------- */
if ($isCli) {
    run_install(true);
    exit;
}

// Interface web
$confirm = ($_GET['confirm'] ?? '') === '1';
?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Installation — Bella Rosa</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#faf6f4;color:#33222a;margin:0;padding:40px 16px;line-height:1.6}
  .card{max-width:680px;margin:0 auto;background:#fff;border-radius:20px;padding:40px;box-shadow:0 20px 60px rgba(183,110,121,.15)}
  h1{font-size:26px;margin:0 0 6px;color:#b76e79}
  p{color:#6b5560}
  .btn{display:inline-block;background:linear-gradient(135deg,#b76e79,#c9a24b);color:#fff;text-decoration:none;padding:14px 28px;border-radius:999px;font-weight:600;margin-top:16px}
  code{background:#f5ece9;padding:2px 7px;border-radius:6px;font-size:13px}
  .line{font-family:ui-monospace,monospace;font-size:13px;padding:3px 0;border-bottom:1px solid #f3e9e6}
  .note{background:#fff8ec;border:1px solid #f0dcae;border-radius:12px;padding:14px 16px;font-size:14px;margin-top:20px;color:#7a5b1e}
</style></head><body><div class="card">
<h1>✨ Installation de Bella Rosa</h1>
<?php if (!$confirm): ?>
  <p>Cet assistant va créer les tables et charger les données de démonstration
     (≈120 produits, 50 clients, 40 commandes, blog, avis…).</p>
  <p>Driver détecté : <code><?php h(Database::driver()); ?></code></p>
  <div class="note">⚠️ Toute donnée existante dans ces tables sera écrasée.
     Pensez à <strong>supprimer ce fichier</strong> après installation.</div>
  <a class="btn" href="?confirm=1">Lancer l'installation →</a>
<?php else:
  try { run_install(false); }
  catch (Throwable $e) { out('❌ Erreur : ' . $e->getMessage(), false); }
?>
  <a class="btn" href="<?php h(url('index.php')); ?>">Voir la boutique →</a>
<?php endif; ?>
</div></body></html>
