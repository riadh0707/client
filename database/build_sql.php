<?php
/** build_sql.php - Génère database.sql (schéma + données). CLI : php database/build_sql.php */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/seed_map.php';

function sql_quote($v): string
{
    if ($v === null) { return 'NULL'; }
    if (is_int($v) || is_float($v)) { return (string) $v; }
    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $v) . "'";
}

$schema = file_get_contents(__DIR__ . '/schema_mysql.sql');
$map = seed_map(demo_data());

$out  = "-- ============================================================================\n";
$out .= "--  La Bibliothèque Numérique : base complète (schéma + démo)\n";
$out .= "--  MySQL 8 / utf8mb4. Admin: admin@bibliotheque-numerique.dz / admin123\n";
$out .= "--  Client: client@bibliotheque-numerique.dz / client123\n";
$out .= "-- ============================================================================\n\n";
$out .= $schema . "\n\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($map as $table => $def) {
    if (empty($def['rows'])) { continue; }
    $cols = '`' . implode('`,`', $def['columns']) . '`';
    $out .= "-- $table (" . count($def['rows']) . " lignes)\n";
    foreach (array_chunk($def['rows'], 50) as $chunk) {
        $values = [];
        foreach ($chunk as $row) { $values[] = '(' . implode(',', array_map('sql_quote', $row)) . ')'; }
        $out .= "INSERT INTO `$table` ($cols) VALUES\n" . implode(",\n", $values) . ";\n";
    }
    $out .= "\n";
}
$out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/database.sql', $out);
echo "database.sql généré (" . number_format(strlen($out)) . " octets)\n";
foreach ($map as $t => $def) { echo "  - $t : " . count($def['rows']) . " lignes\n"; }
