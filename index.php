<?php
declare(strict_types=1);

/**
 * CoreCart - Main Entry Point
 *
 * All HTTP requests go through this file.
 * It bootstraps the engine and dispatches the route.
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

// Load environment variables from .env file
if (file_exists(DIR_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
}

// Register CoreCart autoloader with Safe OCMOD support
spl_autoload_register(function (string $class): void {
    // Only handle CoreCart namespace
    if (strpos($class, 'CoreCart\\') !== 0) {
        return;
    }

    // Convert class name to file path (PSR-4 style)
    $relativePath = str_replace('\\', '/', $class) . '.php';
    $originalFile = DIR_ROOT . '/' . $relativePath;
    $cacheFile = DIR_CACHE . '/modification/' . $relativePath;

    // If a modified version exists in OCMOD cache, try loading it first
    if (file_exists($cacheFile)) {
        try {
            // Safe Mode: syntax-check the cached file before including it
            $code = file_get_contents($cacheFile);
            if ($code !== false && token_get_all($code)) {
                require_once $cacheFile;
                return;
            }
        } catch (\Throwable $e) {
            // Log the error and fall back to the original file
            $logFile = DIR_LOGS . '/ocmod_errors.log';
            $message = date('Y-m-d H:i:s') . ' | ' . $class . ' | ' . $e->getMessage() . PHP_EOL;
            file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
        }
    }

    // Fall back to the original file
    if (file_exists($originalFile)) {
        require_once $originalFile;
    }
});

// Dispatch the incoming request through the Router
$router = new \CoreCart\System\Engine\Router();
$route = $_GET['route'] ?? 'catalog/home/index';
$router->dispatch($route);
