<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Router with DI Container support
 *
 * Converts a route string like "catalog/product/view" into:
 *   - Class: \CoreCart\Catalog\Controller\ProductController
 *   - Method: view()
 *
 * The Container is injected into every controller constructor.
 */
class Router
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a route string to the matching controller and method.
     */
    public function dispatch(string $route): void
    {
        $parts = array_filter(explode('/', trim($route, '/')));

        $side = $parts[0] ?? 'catalog';
        $controllerName = $parts[1] ?? 'home';
        $methodName = $parts[2] ?? 'index';

        $className = '\\CoreCart\\' . ucfirst($side) . '\\Controller\\' . ucfirst($controllerName) . 'Controller';

        if (class_exists($className)) {
            // Inject the container into the controller
            $controller = new $className($this->container);

            if (method_exists($controller, $methodName)) {
                $controller->$methodName();
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => 'Route not found'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
