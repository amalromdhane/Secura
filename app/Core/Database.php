<?php
/**
 * PDO Database Singleton – Secura
 * Replaces the legacy getDBConnection() function.
 * Connection details are read from config/database.php globals.
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(string $database = 'secura'): PDO
    {
        if ($this->pdo instanceof PDO && $database === 'secura') {
            return $this->pdo;
        }

        $configPath = dirname(__DIR__, 2) . '/config/database.php';

        if (!file_exists($configPath)) {
            throw new PDOException("Database config not found at {$configPath}");
        }

        $host   = $GLOBALS['db_host']   ?? '127.0.0.1';
        $user   = $GLOBALS['db_user']   ?? 'root';
        $pass   = $GLOBALS['db_pass']   ?? '';
        $name   = $GLOBALS["db_name_{$database}"]
                ?? $GLOBALS['db_name_secura'] ?? $GLOBALS['db_name_cyber'] ?? 'secura_modules';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $this->pdo;
    }
}
