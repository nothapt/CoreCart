<?php
declare(strict_types=1);

namespace CoreCart\System\Migrations;

use CoreCart\System\Engine\Database;
use PDO;

class MigrationRunner
{
    private Database $db;
    private string $migrationsPath;

    public function __construct(Database $db, string $migrationsPath = '')
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?: dirname(__DIR__, 2) . '/system/migrations';
    }

    public function init(): void
    {
        $pdo = $this->db->getPdo();
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS cc_migration (
                migration_id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(64) NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function getExecutedVersions(): array
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->query('SELECT version FROM cc_migration ORDER BY version');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableMigrations(): array
    {
        $files = glob($this->migrationsPath . '/Version*.php');
        $migrations = [];

        foreach ($files as $file) {
            require_once $file;
            $className = basename($file, '.php');
            $fullClassName = 'CoreCart\\System\\Migrations\\' . $className;

            if (class_exists($fullClassName)) {
                $migration = new $fullClassName();
                $migrations[$migration->getVersion()] = $migration;
            }
        }

        ksort($migrations);
        return $migrations;
    }

    public function getPendingMigrations(): array
    {
        $executed = $this->getExecutedVersions();
        $available = $this->getAvailableMigrations();

        return array_filter($available, fn($m, $v) => !in_array($v, $executed), ARRAY_FILTER_USE_BOTH);
    }

    public function migrateUp(?string $targetVersion = null): array
    {
        $this->init();
        $pending = $this->getPendingMigrations();
        $results = [];

        foreach ($pending as $version => $migration) {
            if ($targetVersion !== null && $version > $targetVersion) {
                break;
            }

            try {
                $pdo = $this->db->getPdo();
                $migration->up($pdo);
                $this->db->execute(
                    'INSERT INTO cc_migration (version, description) VALUES (?, ?)',
                    [$version, $migration->getDescription()]
                );
                $results[] = ['version' => $version, 'status' => 'success'];
            } catch (\Throwable $e) {
                $results[] = ['version' => $version, 'status' => 'failed', 'error' => $e->getMessage()];
                break;
            }
        }

        return $results;
    }

    public function migrateDown(string $targetVersion): array
    {
        $this->init();
        $executed = $this->getExecutedVersions();
        $available = $this->getAvailableMigrations();
        $results = [];

        $toRollback = array_reverse(array_filter($executed, fn($v) => $v > $targetVersion));

        foreach ($toRollback as $version) {
            if (!isset($available[$version])) {
                $results[] = ['version' => $version, 'status' => 'failed', 'error' => 'Migration class not found'];
                break;
            }

            $migration = $available[$version];

            try {
                $this->db->transaction(function ($db) use ($migration, $version) {
                    $migration->down($db->getPdo());
                    $db->execute('DELETE FROM cc_migration WHERE version = ?', [$version]);
                });
                $results[] = ['version' => $version, 'status' => 'rolled_back'];
            } catch (\Throwable $e) {
                $results[] = ['version' => $version, 'status' => 'failed', 'error' => $e->getMessage()];
                break;
            }
        }

        return $results;
    }

    public function status(): array
    {
        $this->init();
        $executed = $this->getExecutedVersions();
        $available = $this->getAvailableMigrations();

        $status = [];
        foreach ($available as $version => $migration) {
            $status[] = [
                'version' => $version,
                'description' => $migration->getDescription(),
                'status' => in_array($version, $executed) ? 'executed' : 'pending',
            ];
        }

        return $status;
    }
}