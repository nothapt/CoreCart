<?php
declare(strict_types=1);

namespace CoreCart\System\View;

use CoreCart\System\Engine\Router;

/**
 * URL generator for Twig templates.
 *
 * Usage in templates: {{ url('catalog/product/view', {'id': 5}) }}
 */
class UrlGenerator
{
    public function __construct(
        private Router $router,
    ) {}

    /**
     * Generate URL for a route with parameters.
     */
    public function route(string $route, array $params = []): string
    {
        return $this->router->url($route, $params);
    }
}
