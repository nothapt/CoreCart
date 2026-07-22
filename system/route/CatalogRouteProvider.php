<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\Catalog\Controller\CategoryController;
use CoreCart\Catalog\Controller\HomeController;
use CoreCart\Catalog\Controller\ProductController;
use CoreCart\System\Engine\Router;
use CoreCart\System\Engine\SecurityHeaders;

final class CatalogRouteProvider
{
    public function register(Router $router): void
    {
        $public = [
            SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
        ];

        $router->addRoutes([
            // Homepage
            '/' => [
                'controller' => HomeController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Catalog: public and internal URLs
            '/catalog' => [
                'controller' => ProductController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            '/catalog/product' => [
                'controller' => ProductController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Search
            '/search' => [
                'controller' => ProductController::class,
                'method'     => 'search',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            '/catalog/product/search' => [
                'controller' => ProductController::class,
                'method'     => 'search',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Product view
            '/product' => [
                'controller' => ProductController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            '/catalog/product/view' => [
                'controller' => ProductController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Categories
            '/categories' => [
                'controller' => CategoryController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            '/catalog/category' => [
                'controller' => CategoryController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            '/category' => [
                'controller' => CategoryController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            '/catalog/category/view' => [
                'controller' => CategoryController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Backward compatibility
            '/catalog/home/index' => [
                'controller' => HomeController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
