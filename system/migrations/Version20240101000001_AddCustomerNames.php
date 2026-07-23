<?php
declare(strict_types=1);

namespace CoreCart\System\Migrations;

use CoreCart\System\Migrations\MigrationInterface;

class Version20240101000001_AddCustomerNames implements MigrationInterface
{
    public function getVersion(): string
    {
        return '20240101000001';
    }

    public function getDescription(): string
    {
        return 'Add firstname and lastname columns to cc_customer table';
    }

    public function up(\PDO $pdo): void
    {
        $cols = $pdo->query("SHOW COLUMNS FROM cc_customer LIKE 'firstname'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE cc_customer ADD COLUMN firstname VARCHAR(128) NOT NULL DEFAULT '' AFTER username");
        }

        $cols = $pdo->query("SHOW COLUMNS FROM cc_customer LIKE 'lastname'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE cc_customer ADD COLUMN lastname VARCHAR(128) NOT NULL DEFAULT '' AFTER firstname");
        }

        $cols = $pdo->query("SHOW COLUMNS FROM cc_customer LIKE 'phone'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE cc_customer ADD COLUMN phone VARCHAR(32) DEFAULT NULL AFTER lastname");
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("ALTER TABLE cc_customer DROP COLUMN IF EXISTS phone");
        $pdo->exec("ALTER TABLE cc_customer DROP COLUMN IF EXISTS lastname");
        $pdo->exec("ALTER TABLE cc_customer DROP COLUMN IF EXISTS firstname");
    }
}