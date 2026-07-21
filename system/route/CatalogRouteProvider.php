<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class CatalogRouteProvider
{
    public function register(Router $router): void
    {
        $public = [
            \CoreCart\System\Engine\SecurityHeaders::class,
        ];

        $router->addRoutes([
            '/' => [
                'controller' => \CoreCart\Catalog\Controller\HomeController::class,
                'method'     => 'index',
                'middleware'  => $public,
            ],
            'catalog/home/index' => [
                'controller' => \CoreCart\Catalog\Controller\HomeController::class,
                'method'     => 'index',
                'middleware'  => $public,
            ],
            'catalog/product' => [
                'controller' => \CoreCart\Catalog\Controller\ProductController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'catalog/product/view' => [
                'controller' => \CoreCart\Catalog\Controller\ProductController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'catalog/product/search' => [
                'controller' => \CoreCart\Catalog\Controller\ProductController::class,
                'method'     => 'search',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'catalog/category' => [
                'controller' => \CoreCart\Catalog\Controller\CategoryController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'catalog/category/view' => [
                'controller' => \CoreCart\Catalog\Controller\CategoryController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
