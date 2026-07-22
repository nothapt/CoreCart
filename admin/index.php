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

$requestId = defined('REQUEST_ID') ? REQUEST_ID : bin2hex(random_bytes(16));
define('REQUEST_ID', $requestId);

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
            $msg = date('Y-m-d H:i:s') . " | " . REQUEST_ID . " | {$class} | " . $e->getMessage() . PHP_EOL;
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

// Infrastructure
$container->set(\CoreCart\System\Infrastructure\SessionInterface::class, fn() => new \CoreCart\System\Infrastructure\Session('CCSESSID_ADMIN'));
$container->set(\CoreCart\System\Engine\Database::class, static fn() => new \CoreCart\System\Engine\Database());

// Repositories
$container->set(\CoreCart\System\Repository\ProductRepository::class, fn($c) => new \CoreCart\System\Repository\ProductRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\CategoryRepository::class, fn($c) => new \CoreCart\System\Repository\CategoryRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\CartRepository::class, fn($c) => new \CoreCart\System\Repository\CartRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\OrderRepository::class, fn($c) => new \CoreCart\System\Repository\OrderRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\CustomerRepository::class, fn($c) => new \CoreCart\System\Repository\CustomerRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\AddressRepository::class, fn($c) => new \CoreCart\System\Repository\AddressRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\SettingRepository::class, fn($c) => new \CoreCart\System\Repository\SettingRepository($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Repository\AdminUserRepository::class, fn($c) => new \CoreCart\System\Repository\AdminUserRepository($c->get(\CoreCart\System\Engine\Database::class)));

// Services
$container->set(\CoreCart\System\Service\CatalogService::class, fn($c) => new \CoreCart\System\Service\CatalogService($c->get(\CoreCart\System\Repository\ProductRepository::class)));
$container->set(\CoreCart\System\Service\CartService::class, fn($c) => new \CoreCart\System\Service\CartService($c->get(\CoreCart\System\Repository\CartRepository::class), $c->get(\CoreCart\System\Repository\ProductRepository::class)));
$container->set(\CoreCart\System\Service\OrderService::class, fn($c) => new \CoreCart\System\Service\OrderService($c->get(\CoreCart\System\Repository\OrderRepository::class)));
$container->set(\CoreCart\System\Service\CustomerService::class, fn($c) => new \CoreCart\System\Service\CustomerService($c->get(\CoreCart\System\Repository\CustomerRepository::class), $c->get(\CoreCart\System\Repository\AddressRepository::class)));
$container->set(\CoreCart\System\Service\CategoryService::class, fn($c) => new \CoreCart\System\Service\CategoryService($c->get(\CoreCart\System\Repository\CategoryRepository::class)));
$container->set(\CoreCart\System\Service\DashboardService::class, fn($c) => new \CoreCart\System\Service\DashboardService($c->get(\CoreCart\System\Repository\AdminUserRepository::class), $c->get(\CoreCart\System\Repository\OrderRepository::class), $c->get(\CoreCart\System\Repository\CustomerRepository::class), $c->get(\CoreCart\System\Repository\ProductRepository::class), $c->get(\CoreCart\System\Repository\CategoryRepository::class)));
$container->set(\CoreCart\System\Service\SettingService::class, fn($c) => new \CoreCart\System\Service\SettingService($c->get(\CoreCart\System\Repository\SettingRepository::class)));

// View (Twig)
$container->set(\CoreCart\System\View\ThemeResolver::class, static fn() => new \CoreCart\System\View\ThemeResolver('admin', 'default'));
$container->set(\CoreCart\System\View\AssetResolver::class, static fn($c) => new \CoreCart\System\View\AssetResolver($c->get(\CoreCart\System\View\ThemeResolver::class)));
$container->set(\CoreCart\System\View\TemplateRendererInterface::class, static fn($c) => new \CoreCart\System\View\TwigRenderer(
    $c->get(\CoreCart\System\View\ThemeResolver::class),
    $c->get(\CoreCart\System\View\AssetResolver::class),
    filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'false'), FILTER_VALIDATE_BOOL),
));
$container->set(\CoreCart\System\View\AdminContextProvider::class, static fn($c) => new \CoreCart\System\View\AdminContextProvider(
    $c->get(\CoreCart\System\Infrastructure\SessionInterface::class),
));

// Middleware & Auth
$container->set(\CoreCart\System\Engine\Validator::class, fn() => new \CoreCart\System\Engine\Validator());
$container->set(\CoreCart\System\Engine\RateLimiter::class, fn($c) => new \CoreCart\System\Engine\RateLimiter($c->get(\CoreCart\System\Engine\Database::class)));
$container->set(\CoreCart\System\Service\AuthService::class, fn($c) => new \CoreCart\System\Service\AuthService($c->get(\CoreCart\System\Repository\AdminUserRepository::class), $c->get(\CoreCart\System\Engine\RateLimiter::class)));
$container->set(\CoreCart\System\Engine\AuthMiddleware::class, fn() => new \CoreCart\System\Engine\AuthMiddleware());
$container->set(\CoreCart\System\Engine\CustomerAuthMiddleware::class, fn() => new \CoreCart\System\Engine\CustomerAuthMiddleware());
$container->set(\CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class, fn() => new \CoreCart\System\Engine\OptionalCustomerAuthMiddleware());
$container->set(\CoreCart\System\Engine\CsrfMiddleware::class, fn() => new \CoreCart\System\Engine\CsrfMiddleware());
$container->set(\CoreCart\System\Engine\SecurityHeaders::class, fn() => new \CoreCart\System\Engine\SecurityHeaders());
$container->set(\CoreCart\System\Engine\RequestMiddleware::class, fn() => new \CoreCart\System\Engine\RequestMiddleware());

// === Build Request ===
$request = \CoreCart\System\Engine\Request::fromGlobals();

// === Register Routes ===
$router = new \CoreCart\System\Engine\Router($container);

(new \CoreCart\System\Route\HealthRouteProvider())->register($router);
(new \CoreCart\System\Route\AdminRouteProvider())->register($router);

// === Dispatch ===
$response = $router->dispatchFromRequest($request);
$response->send();
