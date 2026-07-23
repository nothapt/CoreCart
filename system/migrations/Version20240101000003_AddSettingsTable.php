<?php
declare(strict_types=1);

namespace CoreCart\System\Migrations;

use CoreCart\System\Migrations\MigrationInterface;

class Version20240101000003_AddSettingsTable implements MigrationInterface
{
    public function getVersion(): string
    {
        return '20240101000003';
    }

    public function getDescription(): string
    {
        return 'Create cc_setting table';
    }

    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS cc_setting (
                setting_id INT AUTO_INCREMENT PRIMARY KEY,
                `group` VARCHAR(64) NOT NULL DEFAULT '',
                `key` VARCHAR(128) NOT NULL DEFAULT '',
                `value` TEXT NOT NULL,
                UNIQUE KEY uk_group_key (`group`, `key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS cc_setting");
    }
}
