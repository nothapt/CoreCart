<?php
declare(strict_types=1);

/**
 * CoreCart - Admin Entry Point
 */

define('DIR_ROOT', dirname(__DIR__));
define('DIR_SYSTEM', DIR_ROOT . '/system');
define('DIR_STORAGE', DIR_ROOT . '/storage');
define('DIR_CACHE', DIR_STORAGE . '/cache');
define('DIR_LOGS', DIR_STORAGE . '/logs');
define('DIR_ADMIN', __DIR__);

$requestId = bin2hex(random_bytes(16));
define('REQUEST_ID', $requestId);

// === Session Configuration ===
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', (($_ENV['APP_DEBUG'] ?? 'false') === 'true') ? '0' : '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', '7200');
ini_set('session.cookie_lifetime', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.name', 'CCSESSID');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(DIR_ROOT . '/.env')) {
    require_once DIR_ROOT . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(DIR_ROOT);
    $dotenv->safeLoad();
} else {
    require_once DIR_ROOT . '/vendor/autoload.php';
}

// OCMOD autoloader (prepend)
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
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $msg = sprintf("[%s] %s | %s in %s:%d%s", date('Y-m-d H:i:s'), REQUEST_ID, $error['message'], $error['file'], $error['line'], PHP_EOL);
    @file_put_contents(DIR_LOGS . '/errors.log', $msg, FILE_APPEND | LOCK_EX);
    echo json_encode(['error' => 'Internal server error', 'request_id' => REQUEST_ID], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
});

set_exception_handler(function (\Throwable $e): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $msg = sprintf("[%s] %s | %s in %s:%d%s", date('Y-m-d H:i:s'), REQUEST_ID, $e->getMessage(), $e->getFile(), $e->getLine(), PHP_EOL);
    @file_put_contents(DIR_LOGS . '/errors.log', $msg, FILE_APPEND | LOCK_EX);
    echo json_encode(['error' => 'Internal server error', 'request_id' => REQUEST_ID], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
});

// === Bootstrap DI Container ===
$container = new \CoreCart\System\Engine\Container();
$GLOBALS['corecart_container'] = $container;

$container->set(\CoreCart\System\Engine\Database::class, function () {
    return new \CoreCart\System\Engine\Database(
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'corecart',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? ''
    );
});

$container->set(\CoreCart\System\Engine\Validator::class, fn() => new \CoreCart\System\Engine\Validator());
$container->set(\CoreCart\System\Engine\RateLimiter::class, fn($c) => new \CoreCart\System\Engine\RateLimiter($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Engine\AuthMiddleware::class, fn() => new \CoreCart\System\Engine\AuthMiddleware());
$container->set(\CoreCart\System\Engine\CsrfMiddleware::class, fn() => new \CoreCart\System\Engine\CsrfMiddleware());
$container->set(\CoreCart\System\Engine\SecurityHeaders::class, fn() => new \CoreCart\System\Engine\SecurityHeaders());
$container->set(\CoreCart\System\Engine\RequestMiddleware::class, fn() => new \CoreCart\System\Engine\RequestMiddleware());

// === Register Admin Routes ===
$router = new \CoreCart\System\Engine\Router($container);

$publicMiddleware = [
    \CoreCart\System\Engine\SecurityHeaders::class,
];

$authMiddleware = [
    \CoreCart\System\Engine\AuthMiddleware::class,
    \CoreCart\System\Engine\CsrfMiddleware::class,
];

$router->addRoutes([
    // Public auth routes (no auth required)
    'admin/auth/login' => [
        'controller' => \CoreCart\Admin\Controller\AuthController::class,
        'method'     => 'login',
        'middleware'  => $publicMiddleware,
        'methods'    => ['GET'],
    ],
    'admin/auth/loginPost' => [
        'controller' => \CoreCart\Admin\Controller\AuthController::class,
        'method'     => 'loginPost',
        'middleware'  => array_merge($publicMiddleware, [\CoreCart\System\Engine\RequestMiddleware::class]),
        'methods'    => ['POST'],
    ],
    'admin/auth/logout' => [
        'controller' => \CoreCart\Admin\Controller\AuthController::class,
        'method'     => 'logout',
        'middleware'  => $publicMiddleware,
        'methods'    => ['POST'],
    ],
    'admin/csrf-token' => [
        'controller' => \CoreCart\Admin\Controller\AuthController::class,
        'method'     => 'csrfToken',
        'middleware'  => $authMiddleware,
        'methods'    => ['GET'],
    ],

    // Protected admin routes
    'admin/product/index' => [
        'controller' => \CoreCart\Admin\Controller\ProductController::class,
        'method'     => 'index',
        'middleware'  => $authMiddleware,
    ],
]);

$router->dispatchFromRequest();
