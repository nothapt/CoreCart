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

        // 3. Import tables from install/database.sql
        echo "[3/4] Importing database schema..." . PHP_EOL;
        importSchema($pdo, DIR_ROOT . '/install/database.sql');
        echo "  -> Tables created and seeded." . PHP_EOL;

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
 * Import schema from an SQL file.
 * Splits by semicolons and executes each statement.
 */
function importSchema(PDO $pdo, string $sqlFile): void
{
    if (!file_exists($sqlFile)) {
        throw new \RuntimeException("SQL file not found: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new \RuntimeException("Failed to read SQL file: {$sqlFile}");
    }

    // Remove SQL comments and split by semicolons
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
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
