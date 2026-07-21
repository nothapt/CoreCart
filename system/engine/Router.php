<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Router with route map, HTTP methods, middleware, and security checks
 *
 * - Routes are registered explicitly (no dynamic class/method from URL)
 * - Only public methods, no methods starting with _
 * - HTTP method filtering (GET, POST, PUT, PATCH, DELETE)
 * - Middleware resolved via DI container
 * - Global exception handler with request ID
 */
class Router
{
    private Container $container;

    /**
     * Route map: normalized path => route config
     *
     * @var array<string, array{
     *     controller: string,
     *     method: string,
     *     middleware: string[],
     *     methods: string[]
     * }>
     */
    private array $routes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a route.
     *
     * @param string   $route       Route path, e.g. 'catalog/home/index'
     * @param string   $controller  Fully-qualified controller class name
     * @param string   $method      Method name on the controller
     * @param string[] $middleware   Middleware class names to run first
     * @param string[] $methods     Allowed HTTP methods (empty = all)
     */
    public function addRoute(
        string $route,
        string $controller,
        string $method = 'index',
        array $middleware = [],
        array $methods = []
    ): void {
        $key = $this->normalizeRoute($route);
        $this->routes[$key] = [
            'controller' => $controller,
            'method'     => $method,
            'middleware'  => $middleware,
            'methods'    => array_map('strtoupper', $methods),
        ];
    }

    /**
     * Register multiple routes at once.
     *
     * @param array<string, array{controller: string, method: string, middleware?: string[], methods?: string[]}> $routes
     */
    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route => $config) {
            $this->addRoute(
                $route,
                $config['controller'],
                $config['method'],
                $config['middleware'] ?? [],
                $config['methods'] ?? []
            );
        }
    }

    /**
     * Dispatch a request. Extracts the route path from REQUEST_URI.
     * Falls back to $_GET['route'] for entry-point access (e.g. /admin/index.php?route=...).
     */
    public function dispatchFromRequest(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // If the URI path is an entry point file, use the route query param
        if (preg_match('#/(index\.php)$#', $path) && isset($_GET['route'])) {
            $this->dispatch($_GET['route']);
            return;
        }

        $this->dispatch($path);
    }

    /**
     * Dispatch a route string to the matching controller and method.
     */
    public function dispatch(string $route): void
    {
        $route = $this->normalizeRoute($route);
        $httpMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Look up in route map
        if (!isset($this->routes[$route])) {
            $this->respond404();
            return;
        }

        $config = $this->routes[$route];

        // Check HTTP method
        if (!empty($config['methods']) && !in_array($httpMethod, $config['methods'], true)) {
            $this->respond405($config['methods']);
            return;
        }

        $className = $config['controller'];
        $methodName = $config['method'];

        // Security: method name validation
        if (!$this->isValidMethodName($methodName)) {
            $this->respond404();
            return;
        }

        if (!class_exists($className)) {
            $this->respond404();
            return;
        }

        $controller = new $className($this->container);

        // Security: method must exist and be public
        if (!method_exists($controller, $methodName)) {
            $this->respond404();
            return;
        }

        $reflection = new \ReflectionMethod($controller, $methodName);
        if (!$reflection->isPublic()) {
            $this->respond404();
            return;
        }

        // Run middleware chain, then the controller method
        $this->runMiddleware($config['middleware'], function () use ($controller, $methodName) {
            $controller->$methodName();
        });
    }

    /**
     * Run middleware in order, then call $final.
     * Middleware is resolved via DI container and must implement Middleware.
     *
     * @param string[] $middleware  Middleware class names
     * @param callable $final      The controller action
     */
    private function runMiddleware(array $middleware, callable $final): void
    {
        if (empty($middleware)) {
            $final();
            return;
        }

        $chain = $final;
        foreach (array_reverse($middleware) as $mwClass) {
            $mw = $this->container->get($mwClass);

            if (!$mw instanceof Middleware) {
                throw new \RuntimeException(
                    "Middleware '{$mwClass}' must implement CoreCart\\System\\Engine\\Middleware"
                );
            }

            $prevChain = $chain;
            $chain = function () use ($mw, $prevChain) {
                $mw->handle($prevChain);
            };
        }

        $chain();
    }

    /**
     * Normalize a route string: leading slash, no trailing slash, no double slashes.
     */
    private function normalizeRoute(string $route): string
    {
        $route = strtok($route, '?');
        $route = '/' . trim((string) $route, '/');
        return preg_replace('#/+#', '/', $route);
    }

    /**
     * Validate that a method name is safe to call.
     * Allows only [a-zA-Z0-9_], must not start with _.
     */
    private function isValidMethodName(string $name): bool
    {
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*$/', $name) === 1;
    }

    /**
     * Send a JSON 404 response.
     */
    private function respond404(): void
    {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'Route not found'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Send a JSON 405 response with allowed methods.
     *
     * @param string[] $allowed  Allowed HTTP methods
     */
    private function respond405(array $allowed): void
    {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        header('Allow: ' . implode(', ', $allowed));
        echo json_encode(
            [
                'error' => 'Method not allowed',
                'allowed' => $allowed,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
