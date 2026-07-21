<?php
declare(strict_types=1);

/**
 * CoreCart CLI Installer
 *
 * Usage:
 *   php cli.php install --db_user=root --db_pass=secret --db_name=corecart
 *   php cli.php help
 */

define('DIR_ROOT', __DIR__);
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_LOGS', DIR_STORAGE . '/logs');

require_once DIR_ROOT . '/vendor/autoload.php';

// Load .env if present
if (file_exists(DIR_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
}

// Parse command-line arguments (--key=value)
$args = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--')) {
        [$key, $value] = explode('=', substr($arg, 2), 2);
        $args[$key] = $value ?? '';
    }
}

$command = $argv[1] ?? 'help';

match ($command) {
    'install' => runInstall($args),
    default   => printHelp(),
};

/**
 * Run the installation process.
 */
function runInstall(array $args): void
{
    $dbHost = $args['db_host'] ?? 'localhost';
    $dbUser = $args['db_user'] ?? 'root';
    $dbPass = $args['db_pass'] ?? '';
    $dbName = $args['db_name'] ?? 'corecart';

    echo "=== CoreCart Installer ===" . PHP_EOL;
    echo PHP_EOL;

    try {
        // 1. Test database connection
        echo "[1/4] Testing database connection..." . PHP_EOL;
        $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "  -> Connected successfully." . PHP_EOL;

        // 2. Create database
        echo "[2/4] Creating database (if not exists)..." . PHP_EOL;
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");
        echo "  -> Database `$dbName` is ready." . PHP_EOL;

        // 3. Create basic tables
        echo "[3/4] Creating tables..." . PHP_EOL;
        createTables($pdo);
        echo "  -> Tables created." . PHP_EOL;

        // 4. Write .env file
        echo "[4/4] Writing configuration..." . PHP_EOL;
        $envContent = <<<ENV
        DB_HOST=$dbHost
        DB_NAME=$dbName
        DB_USER=$dbUser
        DB_PASS=$dbPass
        DB_CHARSET=utf8mb4

        APP_NAME=CoreCart
        APP_URL=http://localhost:8000
        APP_DEBUG=true
        ENV;

        file_put_contents(DIR_ROOT . '/.env', $envContent);
        echo "  -> .env file created." . PHP_EOL;

        echo PHP_EOL;
        echo "=== Installation complete! ===" . PHP_EOL;
        echo "Run: php -S localhost:8000 system/engine/router_builtin.php" . PHP_EOL;

    } catch (PDOException $e) {
        echo PHP_EOL . "[ERROR] " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

/**
 * Create the core database tables.
 */
function createTables(PDO $pdo): void
{
    // Products table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product` (
            `product_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `model` VARCHAR(64) NOT NULL DEFAULT '',
            `name` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT,
            `price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            `quantity` INT NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `date_modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `category` (
            `category_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL DEFAULT '',
            `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Users (admin + customers)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user` (
            `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(64) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `group_id` INT UNSIGNED NOT NULL DEFAULT 1,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order` (
            `order_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `status` TINYINT(1) NOT NULL DEFAULT 0,
            `total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            `comment` TEXT,
            `date_added` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `date_modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Settings table (key-value store for configuration)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `setting` (
            `setting_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(128) NOT NULL UNIQUE,
            `value` TEXT,
            `group` VARCHAR(64) NOT NULL DEFAULT 'config'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * Print usage help.
 */
function printHelp(): void
{
    echo <<<HELP

    CoreCart CLI

    Usage:
      php cli.php install [options]     Install CoreCart
      php cli.php help                  Show this message

    Install options:
      --db_host=localhost     Database host (default: localhost)
      --db_user=root          Database user (default: root)
      --db_pass=              Database password (default: empty)
      --db_name=corecart      Database name (default: corecart)

    Example:
      php cli.php install --db_user=root --db_pass=secret --db_name=myshop

    HELP;
}
