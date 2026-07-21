<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Router with Request/Response support
 *
 * - Routes are registered explicitly
 * - HTTP method filtering
 * - Middleware chain with Request/Response
 * - Controllers receive Request, return Response
 */
class Router
{
    private Container $container;

    /**
     * @var array<string, array{controller: string, method: string, middleware: string[], methods: string[]}>
     */
    private array $routes = [];

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

        // Entry-point fallback
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
            return $this->errorResponse('Route not found', 404);
        }

        $config = $this->routes[$route];

        if (!empty($config['methods']) && !in_array($httpMethod, $config['methods'], true)) {
            return $this->methodNotAllowedResponse($config['methods']);
        }

        $className = $config['controller'];
        $methodName = $config['method'];

        if (!$this->isValidMethodName($methodName)) {
            return $this->errorResponse('Invalid method', 404);
        }

        if (!class_exists($className)) {
            return $this->errorResponse('Controller not found', 404);
        }

        $controller = new $className($this->container);

        if (!method_exists($controller, $methodName)) {
            return $this->errorResponse('Method not found', 404);
        }

        $reflection = new \ReflectionMethod($controller, $methodName);
        if (!$reflection->isPublic()) {
            return $this->errorResponse('Method not found', 404);
        }

        return $this->runMiddleware($config['middleware'], $request, function () use ($controller, $methodName, $request) {
            $result = $controller->$methodName($request);
            if ($result instanceof Response) {
                return $result;
            }
            // If controller returns string, wrap in response
            if (is_string($result)) {
                $response = new Response();
                $response->setBody($result);
                $response->setHeader('Content-Type', 'text/html; charset=utf-8');
                return $response;
            }
            return new JsonResponse(null);
        });
    }

    /**
     * Run middleware chain. Returns Response.
     *
     * @param string[] $middleware
     */
    private function runMiddleware(array $middleware, Request $request, callable $final): Response
    {
        if (empty($middleware)) {
            return $final();
        }

        $chain = function () use ($final) {
            return $final();
        };

        foreach (array_reverse($middleware) as $mwClass) {
            $mw = $this->container->get($mwClass);

            if (!$mw instanceof Middleware) {
                throw new \RuntimeException(
                    "Middleware '{$mwClass}' must implement CoreCart\\System\\Engine\\Middleware"
                );
            }

            $prevChain = $chain;
            $chain = function () use ($mw, $prevChain, $request) {
                return $mw->handle($request, $prevChain);
            };
        }

        return $chain();
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

    private function errorResponse(string $message, int $code): JsonResponse
    {
        return new JsonResponse(
            ['error' => $message],
            $code
        );
    }

    private function methodNotAllowedResponse(array $allowed): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Method not allowed', 'allowed' => $allowed],
            405,
            ['Allow' => implode(', ', $allowed)]
        );
    }
}
