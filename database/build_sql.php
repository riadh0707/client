<?php
/**
 * build_sql.php — Génère le fichier livrable database.sql (schéma + données).
 * Usage : php database/build_sql.php  →  écrit database/database.sql
 *
 * Ce script est un outil de build (exécuté par le développeur), il n'est pas
 * appelé par l'application en production.
 */
require_once __DIR__ . '/../includes/functions.php';  // slugify()
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/seed_map.php';

function sql_quote($v): string
{
    if ($v === null) { return 'NULL'; }
    if (is_int($v) || is_float($v)) { return (string) $v; }
    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $v) . "'";
}

$schema = file_get_contents(__DIR__ . '/schema_mysql.sql');
$data   = demo_data();
$map    = seed_map($data);

$out  = "-- ============================================================================\n";
$out .= "--  Bella Rosa — Base de données complète (schéma + données de démonstration)\n";
$out .= "--  Généré automatiquement. MySQL 8 / utf8mb4. Compatible AwardSpace Free.\n";
$out .= "--  Identifiants démo : admin@bella-rosa.dz / admin123  —  client@bella-rosa.dz / client123\n";
$out .= "-- ============================================================================\n\n";
$out .= $schema . "\n\n";
$out .= "-- ============================================================================\n";
$out .= "--  Données de démonstration\n";
$out .= "-- ============================================================================\n";
$out .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($map as $table => $def) {
    if (empty($def['rows'])) { continue; }
    $cols = '`' . implode('`,`', $def['columns']) . '`';
    $out .= "-- $table (" . count($def['rows']) . " lignes)\n";
    // Insertion par lots de 50 lignes pour la lisibilité et la performance.
    foreach (array_chunk($def['rows'], 50) as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $values[] = '(' . implode(',', array_map('sql_quote', $row)) . ')';
        }
        $out .= "INSERT INTO `$table` ($cols) VALUES\n" . implode(",\n", $values) . ";\n";
    }
    $out .= "\n";
}

$out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/database.sql', $out);
echo "database.sql généré (" . number_format(strlen($out)) . " octets)\n";
foreach ($map as $t => $def) {
    echo "  - $t : " . count($def['rows']) . " lignes\n";
}
