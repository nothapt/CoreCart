<?php
declare(strict_types=1);

/**
 * CoreCart - Main Entry Point
 *
 * All frontend HTTP requests go through this file.
 * Bootstraps the engine, registers routes, and dispatches.
 */

// Path constants
define('DIR_ROOT', __DIR__);
define('DIR_SYSTEM', DIR_ROOT . '/system');
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_CACHE', DIR_STORAGE . '/cache');
define('DIR_LOGS', DIR_STORAGE . '/logs');

// Generate unique request ID for logging and debugging
$requestId = bin2hex(random_bytes(16));
define('REQUEST_ID', $requestId);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
if (file_exists(DIR_ROOT . '/.env')) {
    require_once DIR_ROOT . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
} else {
    require_once DIR_ROOT . '/vendor/autoload.php';
}

// OCMOD autoloader (prepend, before Composer)
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
            $msg = date('Y-m-d H:i:s') . " | {$requestId} | {$class} | " . $e->getMessage() . PHP_EOL;
            file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
            @unlink($cacheFile);
        }
    }

    if (file_exists($originalFile)) {
        require_once $originalFile;
    }
}, true, true);

// === Global Exception Handler ===
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }
    // Only handle fatal errors
    if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $logFile = DIR_LOGS . '/errors.log';
    $msg = sprintf(
        "[%s] %s | %s in %s:%d%s",
        date('Y-m-d H:i:s'),
        REQUEST_ID,
        $error['message'],
        $error['file'],
        $error['line'],
        PHP_EOL
    );
    @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    echo json_encode(
        ['error' => 'Internal server error', 'request_id' => REQUEST_ID],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
});

set_exception_handler(function (\Throwable $e): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $logFile = DIR_LOGS . '/errors.log';
    $msg = sprintf(
        "[%s] %s | %s in %s:%d%s",
        date('Y-m-d H:i:s'),
        REQUEST_ID,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        PHP_EOL
    );
    @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    echo json_encode(
        ['error' => 'Internal server error', 'request_id' => REQUEST_ID],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
});

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

// Register middleware in DI container
$container->set(\CoreCart\System\Engine\AuthMiddleware::class, function () {
    return new \CoreCart\System\Engine\AuthMiddleware();
});
$container->set(\CoreCart\System\Engine\CsrfMiddleware::class, function () {
    return new \CoreCart\System\Engine\CsrfMiddleware();
});

// === Register Routes ===
$router = new \CoreCart\System\Engine\Router($container);

$router->addRoutes([
    '/' => [
        'controller' => \CoreCart\Catalog\Controller\HomeController::class,
        'method'     => 'index',
    ],
    'catalog/home/index' => [
        'controller' => \CoreCart\Catalog\Controller\HomeController::class,
        'method'     => 'index',
    ],
]);

// Dispatch: extract route from REQUEST_URI (works with Nginx, Apache, PHP built-in server)
$router->dispatchFromRequest();
