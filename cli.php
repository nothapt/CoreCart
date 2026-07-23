<?php
declare(strict_types=1);

/**
 * CoreCart CLI
 *
 * Usage:
 *   php cli.php install --db_user=root --db_pass=secret --db_name=corecart
 *   php cli.php migrate [options]     Run pending migrations
 *   php cli.php migrate:status        Show migration status
 *   php cli.php migrate:rollback --version=...  Rollback to version
 *   php cli.php help
 */

define('DIR_ROOT', __DIR__);
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_LOGS', DIR_STORAGE . '/logs');

require_once DIR_ROOT . '/vendor/autoload.php';

// Parse command-line arguments (--key=value)
$args = [];
$argvList = $argv ?? [];
foreach ($argvList as $arg) {
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $key = $parts[0];
        $value = $parts[1] ?? '';
        $args[$key] = $value;
    }
}

$command = ($argv ?? [])[1] ?? 'help';

match ($command) {
    'install'        => runInstall($args),
    'migrate'        => runMigrate($args),
    'migrate:status' => runMigrateStatus(),
    'migrate:rollback' => runMigrateRollback($args),
    default          => printHelp(),
};

/**
 * Validate a database name (alphanumeric + underscores only).
 */
function validateDbName(string $name): bool
{
    return preg_match('/^[a-zA-Z0-9_]+$/', $name) === 1;
}

/**
 * Run the installation process.
 */
function runInstall(array $args): void
{
    $dbHost = $args['db_host'] ?? 'localhost';
    $dbUser = $args['db_user'] ?? 'root';
    $dbPass = $args['db_pass'] ?? '';
    $dbName = $args['db_name'] ?? 'corecart';
    $adminUser = $args['admin_user'] ?? 'admin';
    $adminEmail = $args['admin_email'] ?? 'admin@example.com';
    $adminPass = $args['admin_pass'] ?? 'admin123';

    echo "=== CoreCart Installer ===" . PHP_EOL . PHP_EOL;

    // Check if already installed
    if (file_exists(DIR_STORAGE . '/installed.lock')) {
        echo "[ERROR] CoreCart is already installed." . PHP_EOL;
        echo "Remove storage/installed.lock to reinstall." . PHP_EOL;
        exit(1);
    }

    // Validate database name
    if (!validateDbName($dbName)) {
        echo "[ERROR] Invalid database name: '{$dbName}'. Use only a-z, 0-9, underscores." . PHP_EOL;
        exit(1);
    }

    try {
        // 1. Test database connection
        echo "[1/5] Testing database connection..." . PHP_EOL;
        $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "  -> Connected successfully." . PHP_EOL;

        // 2. Create database
        echo "[2/5] Creating database (if not exists)..." . PHP_EOL;
        $stmt = $pdo->prepare("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $stmt->execute();
        $pdo->exec("USE `$dbName`");
        echo "  -> Database `{$dbName}` is ready." . PHP_EOL;

        // 3. Import tables (skip admin_user seed, we'll create it properly)
        echo "[3/5] Importing database schema..." . PHP_EOL;
        importSchema($pdo, DIR_ROOT . '/install/database.sql');
        echo "  -> Tables created and seeded." . PHP_EOL;

        // 4. Create admin user with hashed password
        echo "[4/5] Creating admin user..." . PHP_EOL;
        $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            "INSERT INTO cc_admin_user (username, email, password, status)
             VALUES (:username, :email, :password, 1)
             ON DUPLICATE KEY UPDATE password = :password2"
        );
        $stmt->execute([
            'username'  => $adminUser,
            'email'     => $adminEmail,
            'password'  => $hash,
            'password2' => $hash,
        ]);
        echo "  -> Admin user `{$adminUser}` created." . PHP_EOL;

        // 5. Write .env and installed.lock
        echo "[5/5] Writing configuration..." . PHP_EOL;
        $envContent = <<<ENV
        DB_HOST=$dbHost
        DB_NAME=$dbName
        DB_USER=$dbUser
        DB_PASS=$dbPass
        DB_CHARSET=utf8mb4

        APP_NAME=CoreCart
        APP_URL=http://localhost:8000
        APP_DEBUG=false
        ENV;

        file_put_contents(DIR_ROOT . '/.env', $envContent);
        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod(DIR_ROOT . '/.env', 0600);
        }

        // Create installed.lock
        file_put_contents(DIR_STORAGE . '/installed.lock', date('Y-m-d H:i:s') . PHP_EOL);
        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod(DIR_STORAGE . '/installed.lock', 0400);
        }

        echo "  -> .env and installed.lock created." . PHP_EOL;

        echo PHP_EOL;
        echo "=== Installation complete! ===" . PHP_EOL;
        echo "Admin: {$adminUser} / {$adminPass}" . PHP_EOL;
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

    // Remove SQL comments, split by semicolons
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        $pdo->exec($statement);
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
      php cli.php install [options]        Install CoreCart
      php cli.php migrate [options]        Run pending migrations
      php cli.php migrate:status           Show migration status
      php cli.php migrate:rollback [opts]  Rollback to version
      php cli.php help                     Show this message

    Install options:
      --db_host=localhost     Database host (default: localhost)
      --db_user=root          Database user (default: root)
      --db_pass=              Database password (default: empty)
      --db_name=corecart      Database name (default: corecart)
      --admin_user=admin      Admin username (default: admin)
      --admin_email=admin@example.com  Admin email
      --admin_pass=admin123   Admin password (default: admin123)

    Migrate options:
      --version=VERSION       Target version (optional)

    Example:
      php cli.php install --db_user=root --db_pass=secret --db_name=myshop --admin_pass=mypassword
      php cli.php migrate
      php cli.php migrate:status
      php cli.php migrate:rollback --version=20240101000001

    HELP;
}

/**
 * Run pending migrations.
 */
function runMigrate(array $args): void
{
    $dbHost = $args['db_host'] ?? 'localhost';
    $dbUser = $args['db_user'] ?? 'root';
    $dbPass = $args['db_pass'] ?? '';
    $dbName = $args['db_name'] ?? 'corecart';
    $targetVersion = $args['version'] ?? null;

    echo "=== CoreCart Migrations ===" . PHP_EOL . PHP_EOL;

    try {
        $db = new \CoreCart\System\Engine\Database(host: $dbHost, name: $dbName, user: $dbUser, pass: $dbPass);
        $runner = new \CoreCart\System\Migrations\MigrationRunner($db);

        $results = $runner->migrateUp($targetVersion);

        if (empty($results)) {
            echo "No pending migrations." . PHP_EOL;
        } else {
            foreach ($results as $result) {
                $status = $result['status'];
                $version = $result['version'];
                if ($status === 'success') {
                    echo "[OK] $version" . PHP_EOL;
                } else {
                    echo "[FAIL] $version: " . ($result['error'] ?? 'unknown') . PHP_EOL;
                }
            }
        }

        echo PHP_EOL . "Done." . PHP_EOL;

    } catch (\Throwable $e) {
        echo PHP_EOL . "[ERROR] " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

/**
 * Show migration status.
 */
function runMigrateStatus(): void
{
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbUser = $_ENV['DB_USER'] ?? 'root';
    $dbPass = $_ENV['DB_PASS'] ?? '';
    $dbName = $_ENV['DB_NAME'] ?? 'corecart';

    echo "=== Migration Status ===" . PHP_EOL . PHP_EOL;

    try {
        $db = new \CoreCart\System\Engine\Database(host: $dbHost, name: $dbName, user: $dbUser, pass: $dbPass);
        $runner = new \CoreCart\System\Migrations\MigrationRunner($db);

        $status = $runner->status();

        if (empty($status)) {
            echo "No migrations found." . PHP_EOL;
        } else {
            foreach ($status as $item) {
                $icon = $item['status'] === 'executed' ? '[OK]' : '[--]';
                echo "$icon {$item['version']}: {$item['description']}" . PHP_EOL;
            }
        }

    } catch (\Throwable $e) {
        echo "[ERROR] " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

/**
 * Rollback migrations to target version.
 */
function runMigrateRollback(array $args): void
{
    $dbHost = $args['db_host'] ?? 'localhost';
    $dbUser = $args['db_user'] ?? 'root';
    $dbPass = $args['db_pass'] ?? '';
    $dbName = $args['db_name'] ?? 'corecart';
    $targetVersion = $args['version'] ?? '';

    if ($targetVersion === '') {
        echo "[ERROR] --version is required for rollback" . PHP_EOL;
        exit(1);
    }

    echo "=== Rollback to $targetVersion ===" . PHP_EOL . PHP_EOL;

    try {
        $db = new \CoreCart\System\Engine\Database(host: $dbHost, name: $dbName, user: $dbUser, pass: $dbPass);
        $runner = new \CoreCart\System\Migrations\MigrationRunner($db);

        $results = $runner->migrateDown($targetVersion);

        foreach ($results as $result) {
            $status = $result['status'];
            $version = $result['version'];
            if ($status === 'rolled_back') {
                echo "[OK] Rolled back $version" . PHP_EOL;
            } else {
                echo "[FAIL] $version: " . ($result['error'] ?? 'unknown') . PHP_EOL;
            }
        }

        echo PHP_EOL . "Done." . PHP_EOL;

    } catch (\Throwable $e) {
        echo PHP_EOL . "[ERROR] " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
