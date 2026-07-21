<?php
declare(strict_types=1);

/**
 * CoreCart - Main Entry Point
 *
 * All frontend HTTP requests go through this file.
 * It bootstraps the engine, registers services in the DI container,
 * and dispatches the route.
 */

// Path constants
define('DIR_ROOT', __DIR__);
define('DIR_SYSTEM', DIR_ROOT . '/system');
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_CACHE', DIR_STORAGE . '/cache');
define('DIR_LOGS', DIR_STORAGE . '/logs');

// Start session for flash messages, cart, auth, etc.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoloader
require_once DIR_ROOT . '/vendor/autoload.php';

// Load environment variables from .env
if (file_exists(DIR_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
}

// Register CoreCart autoloader with Safe OCMOD support
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'CoreCart\\') !== 0) {
        return;
    }

    $relativePath = str_replace('\\', '/', $class) . '.php';
    $originalFile = DIR_ROOT . '/' . $relativePath;
    $cacheFile = DIR_CACHE . '/modification/' . $relativePath;

    // Try modified version first (Safe OCMOD)
    if (file_exists($cacheFile)) {
        try {
            $code = file_get_contents($cacheFile);
            if ($code !== false && token_get_all($code)) {
                require_once $cacheFile;
                return;
            }
        } catch (\Throwable $e) {
            $logFile = DIR_LOGS . '/ocmod_errors.log';
            $message = date('Y-m-d H:i:s') . ' | ' . $class . ' | ' . $e->getMessage() . PHP_EOL;
            file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
        }
    }

    // Fall back to original
    if (file_exists($originalFile)) {
        require_once $originalFile;
    }
});

// === Bootstrap DI Container ===
$container = new \CoreCart\System\Engine\Container();

// Register Database as a singleton
$container->set(\CoreCart\System\Engine\Database::class, function () {
    return new \CoreCart\System\Engine\Database(
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'corecart',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? ''
    );
});

// Dispatch the request
$router = new \CoreCart\System\Engine\Router($container);
$router->dispatch($_GET['route'] ?? 'catalog/home/index');
