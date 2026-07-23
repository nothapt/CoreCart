<?php
declare(strict_types=1);

namespace CoreCart\System\Migrations;

use CoreCart\System\Migrations\MigrationInterface;

class Version20240101000002_AddOrderHistory implements MigrationInterface
{
    public function getVersion(): string
    {
        return '20240101000002';
    }

    public function getDescription(): string
    {
        return 'Add cc_order_history table for status change tracking';
    }

    public function up(\PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS cc_order_history (
                order_history_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                status TINYINT NOT NULL,
                comment TEXT NOT NULL,
                notify_customer TINYINT(1) NOT NULL DEFAULT 0,
                admin_user_id INT DEFAULT NULL,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES cc_order(order_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS cc_order_history");
    }
}