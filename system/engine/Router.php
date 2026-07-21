<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Router with route map, middleware, and security checks
 *
 * Routes are registered explicitly. Only public methods on controllers
 * are accessible. Methods starting with _ are always blocked.
 * Middleware runs in order before the controller action.
 */
class Router
{
    private Container $container;

    /** @var array<string, array{controller: string, method: string, middleware: string[]}> */
    private array $routes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a route.
     *
     * @param string $route       Route path, e.g. 'catalog/home/index'
     * @param string $controller  Fully-qualified controller class name
     * @param string $method      Method name on the controller
     * @param string[] $middleware List of middleware class names to run first
     */
    public function addRoute(string $route, string $controller, string $method = 'index', array $middleware = []): void
    {
        // Normalize route key to match dispatch format: /catalog/home/index
        $key = '/' . trim($route, '/');
        $key = preg_replace('#/+#', '/', $key);
        $this->routes[$key] = [
            'controller' => $controller,
            'method'     => $method,
            'middleware'  => $middleware,
        ];
    }

    /**
     * Register multiple routes at once.
     *
     * @param array<string, array{controller: string, method: string, middleware?: string[]}> $routes
     */
    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route => $config) {
            $this->addRoute(
                $route,
                $config['controller'],
                $config['method'],
                $config['middleware'] ?? []
            );
        }
    }

    /**
     * Dispatch a route string to the matching controller and method.
     */
    public function dispatch(string $route): void
    {
        // Normalize: strip query string, trim slashes, collapse doubles
        $route = is_string($route) ? strtok($route, '?') : '';
        $route = '/' . trim((string) $route, '/');
        $route = preg_replace('#/+#', '/', $route);

        // Look up in route map
        if (!isset($this->routes[$route])) {
            $this->respond404();
            return;
        }

        $config = $this->routes[$route];
        $className = $config['controller'];
        $methodName = $config['method'];

        // Security: method must be valid PHP identifier, no leading _
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
     *
     * @param string[]    $middleware  Middleware class names
     * @param callable    $final      The controller action
     */
    private function runMiddleware(array $middleware, callable $final): void
    {
        if (empty($middleware)) {
            $final();
            return;
        }

        // Build chain: last middleware calls $final, each previous calls the next
        $chain = $final;
        foreach (array_reverse($middleware) as $mwClass) {
            $mw = new $mwClass();
            $prevChain = $chain;
            $chain = function () use ($mw, $prevChain) {
                $mw->handle($prevChain);
            };
        }

        // Start the chain
        $chain();
    }

    /**
     * Validate that a method name is safe to call.
     * Allows only [a-zA-Z0-9_], must not start with _.
     */
    private function isValidMethodName(string $name): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            return false;
        }
        if ($name[0] === '_') {
            return false;
        }
        return true;
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
}
