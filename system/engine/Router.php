<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Simple PSR-4 style router.
 *
 * Converts a route string like "catalog/product/view" into:
 *   - Class: \CoreCart\Catalog\Controller\ProductController
 *   - Method: view()
 *
 * If the class or method is not found, returns a 404 page.
 */
class Router
{
    /**
     * Dispatch a route string to the matching controller and method.
     */
    public function dispatch(string $route): void
    {
        $parts = array_filter(explode('/', trim($route, '/')));

        // Determine which side: admin or catalog (default: catalog)
        $side = $parts[0] ?? 'catalog';
        $controllerName = $parts[1] ?? 'home';
        $methodName = $parts[2] ?? 'index';

        // Build the fully-qualified class name
        $className = '\\CoreCart\\' . ucfirst($side) . '\\Controller\\' . ucfirst($controllerName) . 'Controller';

        if (class_exists($className)) {
            $controller = new $className();

            if (method_exists($controller, $methodName)) {
                $controller->$methodName();
                return;
            }
        }

        // Nothing matched - send 404
        http_response_code(404);
        echo $this->render404($route);
    }

    /**
     * Render a minimal 404 error page.
     */
    private function render404(string $route): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>404 - Not Found</title>
            <style>
                body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f6f9; color: #333; }
                .box { text-align: center; }
                h1 { font-size: 72px; margin: 0; color: #272d3b; }
                p { font-size: 18px; color: #666; }
                a { color: #3b82f6; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="box">
                <h1>404</h1>
                <p>Route <code>{$route}</code> was not found.</p>
                <a href="/">Go back to首页</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
