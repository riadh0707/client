<?php
/**
 * Database — fine surcouche PDO (singleton).
 *
 * Sécurité : PDO exclusivement, requêtes préparées, ERRMODE_EXCEPTION.
 * Portabilité : supporte MySQL (production/AwardSpace) et SQLite (démo locale).
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    /** Retourne la connexion PDO partagée. */
    public static function pdo(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $cfg    = $GLOBALS['config']['db'];
        $driver = $cfg['driver'] ?? 'mysql';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . $cfg['sqlite_path'];
                self::$instance = new PDO($dsn, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $cfg['host'], $cfg['port'] ?? 3306, $cfg['name'], $cfg['charset'] ?? 'utf8mb4'
                );
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            }
        } catch (PDOException $e) {
            if (($GLOBALS['config']['app']['env'] ?? 'production') === 'development') {
                http_response_code(500);
                exit('Erreur de connexion à la base de données : ' . $e->getMessage());
            }
            http_response_code(503);
            exit('Le service est momentanément indisponible. Veuillez réessayer.');
        }

        return self::$instance;
    }

    /** Indique le driver actif ('mysql' | 'sqlite'). */
    public static function driver(): string
    {
        return $GLOBALS['config']['db']['driver'] ?? 'mysql';
    }

    /* --------------------------------------------------------------------
     |  Raccourcis pratiques
     * -------------------------------------------------------------------- */

    /** Exécute une requête préparée et renvoie le PDOStatement. */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Première ligne (ou null). */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Toutes les lignes. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Valeur scalaire de la première colonne. */
    public static function scalar(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }

    /** Insert et renvoie l'ID généré. */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }
}
