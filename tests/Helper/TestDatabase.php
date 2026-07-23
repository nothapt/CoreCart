<?php
declare(strict_types=1);

namespace CoreCart\Tests\Helper;

use CoreCart\System\Engine\Database;

class TestDatabase extends Database
{
    private static bool $schemaReady = false;

    public function __construct()
    {
        parent::__construct(
            host: 'localhost',
            name: 'corecart_test',
            user: 'root',
            pass: '1234',
            port: 3306,
        );

        if (!self::$schemaReady) {
            $this->createTestSchema();
            self::$schemaReady = true;
        } else {
            $this->truncateAll();
        }
    }

    private function truncateAll(): void
    {
        $pdo = $this->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['cc_order_history','cc_order_product','cc_order','cc_cart','cc_product_description','cc_product','cc_category_description','cc_category','cc_address','cc_customer','cc_admin_user','cc_setting','cc_migration'] as $table) {
            $pdo->exec("TRUNCATE TABLE {$table}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function createTestSchema(): void
    {
        $pdo = $this->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'cc_customer',
            'cc_product',
            'cc_product_description',
            'cc_category',
            'cc_category_description',
            'cc_cart',
            'cc_order',
            'cc_order_product',
            'cc_order_history',
            'cc_address',
            'cc_admin_user',
            'cc_setting',
            'cc_migration',
        ];

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$table}");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $pdo->exec(<<<SQL
            CREATE TABLE cc_customer (
                customer_id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL DEFAULT '',
                firstname VARCHAR(128) NOT NULL DEFAULT '',
                lastname VARCHAR(128) NOT NULL DEFAULT '',
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                status INT NOT NULL DEFAULT 1,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_product (
                product_id INT AUTO_INCREMENT PRIMARY KEY,
                model VARCHAR(64) NOT NULL DEFAULT '',
                sku VARCHAR(64) NOT NULL DEFAULT '',
                price DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                quantity INT NOT NULL DEFAULT 0,
                image VARCHAR(255) NOT NULL DEFAULT '',
                status INT NOT NULL DEFAULT 1,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_product_description (
                product_id INT NOT NULL,
                language_id INT NOT NULL DEFAULT 1,
                name VARCHAR(255) NOT NULL DEFAULT '',
                description TEXT,
                PRIMARY KEY (product_id, language_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_category (
                category_id INT AUTO_INCREMENT PRIMARY KEY,
                status INT NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_category_description (
                category_id INT NOT NULL,
                language_id INT NOT NULL DEFAULT 1,
                name VARCHAR(255) NOT NULL DEFAULT '',
                description TEXT,
                PRIMARY KEY (category_id, language_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_cart (
                cart_id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) NOT NULL DEFAULT '',
                customer_id INT,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_order (
                order_id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT,
                status INT NOT NULL DEFAULT 0,
                total DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                comment TEXT,
                customer_email VARCHAR(255) NOT NULL DEFAULT '',
                customer_phone VARCHAR(32),
                shipping_firstname VARCHAR(128) NOT NULL DEFAULT '',
                shipping_lastname VARCHAR(128) NOT NULL DEFAULT '',
                shipping_address_1 VARCHAR(256) NOT NULL DEFAULT '',
                shipping_address_2 VARCHAR(256),
                shipping_city VARCHAR(128) NOT NULL DEFAULT '',
                shipping_postcode VARCHAR(32) NOT NULL DEFAULT '',
                shipping_country VARCHAR(64) NOT NULL DEFAULT '',
                shipping_zone VARCHAR(64) NOT NULL DEFAULT '',
                currency_code VARCHAR(3) NOT NULL DEFAULT 'USD',
                currency_value DECIMAL(15,4) NOT NULL DEFAULT 1.0000,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_order_product (
                order_product_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                name VARCHAR(255) NOT NULL DEFAULT '',
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(15,4) NOT NULL DEFAULT 0.0000
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_order_history (
                order_history_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                status INT NOT NULL DEFAULT 0,
                comment TEXT,
                notify_customer INT NOT NULL DEFAULT 0,
                admin_user_id INT,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_address (
                address_id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                firstname VARCHAR(128) NOT NULL DEFAULT '',
                lastname VARCHAR(128) NOT NULL DEFAULT '',
                address_1 VARCHAR(256) NOT NULL DEFAULT '',
                address_2 VARCHAR(256) NOT NULL DEFAULT '',
                city VARCHAR(128) NOT NULL DEFAULT '',
                postcode VARCHAR(32) NOT NULL DEFAULT '',
                country VARCHAR(64) NOT NULL DEFAULT '',
                zone VARCHAR(64) NOT NULL DEFAULT '',
                `default` INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_admin_user (
                admin_user_id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                status INT NOT NULL DEFAULT 1,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_setting (
                setting_id INT AUTO_INCREMENT PRIMARY KEY,
                `group` VARCHAR(64) NOT NULL,
                `key` VARCHAR(128) NOT NULL,
                value TEXT,
                UNIQUE KEY (`group`, `key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $pdo->exec(<<<SQL
            CREATE TABLE cc_migration (
                migration_id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(64) NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);
    }
}
