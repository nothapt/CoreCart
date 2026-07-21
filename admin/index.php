<?php
declare(strict_types=1);

/**
 * CoreCart - Admin Entry Point
 *
 * Separate entry for the admin panel.
 * Same bootstrap logic as the front-end, just different root path.
 */

define('DIR_ROOT', dirname(__DIR__));
define('DIR_SYSTEM', DIR_ROOT . '/system');
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_CACHE', DIR_STORAGE . '/cache');
define('DIR_LOGS', DIR_STORAGE . '/logs');
define('DIR_ADMIN', __DIR__);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once DIR_ROOT . '/vendor/autoload.php';

if (file_exists(DIR_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
}

// Same autoloader with Safe OCMOD support
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'CoreCart\\') !== 0) {
        return;
    }

    $relativePath = str_replace('\\', '/', $class) . '.php';
    $originalFile = DIR_ROOT . '/' . $relativePath;
    $cacheFile = DIR_CACHE . '/modification/' . $relativePath;

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

    if (file_exists($originalFile)) {
        require_once $originalFile;
    }
});

$router = new \CoreCart\System\Engine\Router();
$route = $_GET['route'] ?? 'admin/dashboard/index';
$router->dispatch($route);
