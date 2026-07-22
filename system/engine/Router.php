<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

class Router
{
    private Container $container;

    /**
     * @var array<string, array{controller: string, method: string, middleware: string[], methods: string[]}>
     */
    private array $routes = [];

    /**
     * @var array<string, string> Named routes: name → normalized route path
     */
    private array $routeNames = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

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
     * Dispatch from the global Request. Returns a Response.
     */
    public function dispatchFromRequest(Request $request): Response
    {
        $path = $request->getPath();

        if (preg_match('#/(index\.php)$#', $path)) {
            $routeParam = $request->getQueryParam('route', '');
            if ($routeParam !== '') {
                return $this->dispatch($routeParam, $request);
            }
        }

        return $this->dispatch($path, $request);
    }

    /**
     * Dispatch a route. Returns Response.
     */
    public function dispatch(string $route, Request $request): Response
    {
        $route = $this->normalizeRoute($route);
        $httpMethod = $request->getMethod();

        if (!isset($this->routes[$route])) {
            return new JsonResponse(['error' => 'Route not found'], 404);
        }

        $config = $this->routes[$route];

        if (!empty($config['methods']) && !in_array($httpMethod, $config['methods'], true)) {
            return new JsonResponse(
                ['error' => 'Method not allowed', 'allowed' => $config['methods']],
                405,
                ['Allow' => implode(', ', $config['methods'])]
            );
        }

        $className = $config['controller'];
        $methodName = $config['method'];

        if (!$this->isValidMethodName($methodName)) {
            return new JsonResponse(['error' => 'Invalid method'], 404);
        }

        if (!class_exists($className)) {
            return new JsonResponse(['error' => 'Controller not found'], 404);
        }

        $controller = new $className($this->container);

        if (!method_exists($controller, $methodName)) {
            return new JsonResponse(['error' => 'Method not found'], 404);
        }

        $reflection = new \ReflectionMethod($controller, $methodName);
        if (!$reflection->isPublic()) {
            return new JsonResponse(['error' => 'Method not found'], 404);
        }

        // Build the final controller callable that accepts the current Request
        $controllerCallable = static fn(Request $currentRequest): Response =>
            $controller->$methodName($currentRequest);

        return $this->runMiddleware($config['middleware'], $request, $controllerCallable);
    }

    /**
     * Run middleware in order, passing the (possibly modified) Request through each layer.
     *
     * @param string[] $middleware  Middleware class names
     */
    private function runMiddleware(array $middleware, Request $request, callable $controller): Response
    {
        if (empty($middleware)) {
            return $controller($request);
        }

        // Start from the innermost: the controller
        $next = $controller;

        // Wrap each middleware around, innermost first
        foreach (array_reverse($middleware) as $mwClass) {
            $mw = $this->container->get($mwClass);

            if (!$mw instanceof Middleware) {
                throw new \RuntimeException(
                    "Middleware '{$mwClass}' must implement CoreCart\\System\\Engine\\Middleware"
                );
            }

            $previous = $next;
            // Each middleware receives the current Request and a $next that accepts Request
            $next = static fn(Request $currentRequest): Response =>
                $mw->handle($currentRequest, $previous);
        }

        // Kick off the chain with the original request
        return $next($request);
    }

    /**
     * Generate a URL for a named route.
     *
     * Replaces {param} placeholders with values from $params.
     * Remaining params are appended as query string.
     *
     * Example: $router->url('product/view', ['id' => 5]) → '/catalog/product/view?id=5'
     */
    public function url(string $route, array $params = []): string
    {
        $normalizedRoute = $this->normalizeRoute($route);

        if (!isset($this->routes[$normalizedRoute])) {
            return '/' . ltrim($route, '/');
        }

        // Replace {param} placeholders in the route pattern
        $url = preg_replace_callback('#\{(\w+)\}#', static function (array $matches) use (&$params): string {
            $key = $matches[1];
            $value = $params[$key] ?? '';
            unset($params[$key]);
            return (string) $value;
        }, $normalizedRoute);

        // Append remaining params as query string
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Register a named route (alias for addRoute with a name).
     */
    public function addNamedRoute(
        string $name,
        string $route,
        string $controller,
        string $method = 'index',
        array $middleware = [],
        array $methods = [],
    ): void {
        $this->addRoute($route, $controller, $method, $middleware, $methods);
        $this->routeNames[$name] = $this->normalizeRoute($route);
    }

    /**
     * Get the URL for a named route.
     */
    public function namedUrl(string $name, array $params = []): string
    {
        $route = $this->routeNames[$name] ?? null;
        if ($route === null) {
            return '/';
        }
        return $this->url($route, $params);
    }

    private function normalizeRoute(string $route): string
    {
        $route = strtok($route, '?');
        $route = '/' . trim((string) $route, '/');
        return preg_replace('#/+#', '/', $route);
    }

    private function isValidMethodName(string $name): bool
    {
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_]*$/', $name) === 1;
    }
}
