<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class AdminRouteProvider
{
    public function register(Router $router): void
    {
        $public = [
            \CoreCart\System\Engine\SecurityHeaders::class,
        ];

        $auth = [
            \CoreCart\System\Engine\AuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
        ];

        $authWithRequest = [
            \CoreCart\System\Engine\AuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        $router->addRoutes([
            // Auth
            'admin/auth/login' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'login',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'admin/auth/loginPost' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'loginPost',
                'middleware'  => array_merge($public, [\CoreCart\System\Engine\RequestMiddleware::class]),
                'methods'    => ['POST'],
            ],
            'admin/auth/logout' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'logout',
                'middleware'  => $public,
                'methods'    => ['POST'],
            ],
            'admin/csrf-token' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'csrfToken',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],

            // Dashboard
            'admin/dashboard' => [
                'controller' => \CoreCart\Admin\Controller\DashboardController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],

            // Products
            'admin/product/index' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'admin/product/create' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'create',
                'middleware'  => $authWithRequest,
                'methods'    => ['POST'],
            ],
            'admin/product/update' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'update',
                'middleware'  => $authWithRequest,
                'methods'    => ['POST'],
            ],
            'admin/product/delete' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'delete',
                'middleware'  => $auth,
                'methods'    => ['POST'],
            ],

            // Categories
            'admin/category/index' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'admin/category/create' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'create',
                'middleware'  => $authWithRequest,
                'methods'    => ['POST'],
            ],
            'admin/category/update' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'update',
                'middleware'  => $authWithRequest,
                'methods'    => ['POST'],
            ],
            'admin/category/delete' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'delete',
                'middleware'  => $auth,
                'methods'    => ['POST'],
            ],

            // Orders
            'admin/order/index' => [
                'controller' => \CoreCart\Admin\Controller\OrderController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'admin/order/view' => [
                'controller' => \CoreCart\Admin\Controller\OrderController::class,
                'method'     => 'view',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'admin/order/updateStatus' => [
                'controller' => \CoreCart\Admin\Controller\OrderController::class,
                'method'     => 'updateStatus',
                'middleware'  => $authWithRequest,
                'methods'    => ['POST'],
            ],

            // Customers
            'admin/customer/index' => [
                'controller' => \CoreCart\Admin\Controller\CustomerController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'admin/customer/view' => [
                'controller' => \CoreCart\Admin\Controller\CustomerController::class,
                'method'     => 'view',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
