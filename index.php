<?php
declare(strict_types=1);

/**
 * CoreCart - Main Entry Point
 *
 * All frontend HTTP requests go through this file.
 * It bootstraps the engine, registers routes, and dispatches.
 */

// Path constants
define('DIR_ROOT', __DIR__);
define('DIR_SYSTEM', DIR_ROOT . '/system');
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_CACHE', DIR_STORAGE . '/cache');
define('DIR_LOGS', DIR_STORAGE . '/logs');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables FIRST (before anything reads $_ENV)
if (file_exists(DIR_ROOT . '/.env')) {
    require_once DIR_ROOT . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
} else {
    require_once DIR_ROOT . '/vendor/autoload.php';
}

// Register OCMOD autoloader BEFORE Composer's autoloader
// This ensures modified files take priority over originals
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'CoreCart\\') !== 0) {
        return;
    }

    $relativePath = str_replace('\\', '/', substr($class, strlen('CoreCart\\'))) . '.php';
    $cacheFile = DIR_CACHE . '/modification/' . $relativePath;
    $originalFile = DIR_ROOT . '/system/' . $relativePath;

    if (file_exists($cacheFile)) {
        try {
            $code = file_get_contents($cacheFile);
            if ($code !== false && token_get_all($code)) {
                require_once $cacheFile;
                return;
            }
        } catch (\Throwable $e) {
            $logFile = DIR_LOGS . '/ocmod_errors.log';
            $msg = date('Y-m-d H:i:s') . ' | ' . $class . ' | ' . $e->getMessage() . PHP_EOL;
            file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
            // Remove broken cache file, fall through to original
            @unlink($cacheFile);
        }
    }

    if (file_exists($originalFile)) {
        require_once $originalFile;
    }
}, true, true); // prepend = true

// === Bootstrap DI Container ===
$container = new \CoreCart\System\Engine\Container();

$container->set(\CoreCart\System\Engine\Database::class, function () {
    return new \CoreCart\System\Engine\Database(
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'corecart',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? ''
    );
});

// === Register Routes ===
$router = new \CoreCart\System\Engine\Router($container);

$router->addRoutes([
    'catalog/home/index' => [
        'controller' => \CoreCart\Catalog\Controller\HomeController::class,
        'method'     => 'index',
    ],
]);

// Dispatch
$router->dispatch($_GET['route'] ?? 'catalog/home/index');
