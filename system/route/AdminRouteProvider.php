<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class AdminRouteProvider
{
    public function register(Router $router): void
    {
        $publicForm = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
        ];

        $publicMutation = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        $authenticated = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\AuthMiddleware::class,
        ];

        $authenticatedMutation = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\AuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        // Admin login: GET shows form, POST processes login
        // Now supports both HTTP methods on the same path
        $router->addRoute('/admin', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/login', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/login', \CoreCart\Admin\Controller\AuthController::class, 'loginPost', $publicMutation, ['POST']);

        $router->addRoutes([
            // Auth (backward compatibility)
            'admin/auth/login' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'login',
                'middleware'  => $publicForm,
                'methods'    => ['GET'],
            ],
            'admin/auth/loginPost' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'loginPost',
                'middleware'  => $publicMutation,
                'methods'    => ['POST'],
            ],
            'admin/auth/logout' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'logout',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],
            'admin/csrf-token' => [
                'controller' => \CoreCart\Admin\Controller\AuthController::class,
                'method'     => 'csrfToken',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],

            // Dashboard
            'admin/dashboard' => [
                'controller' => \CoreCart\Admin\Controller\DashboardController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],

            // Products
            'admin/product/index' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/product/create' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'create',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/product/createPost' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'createPost',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],
            'admin/product/edit' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'edit',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/product/update' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'update',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],
            'admin/product/delete' => [
                'controller' => \CoreCart\Admin\Controller\ProductController::class,
                'method'     => 'delete',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],

            // Categories
            'admin/category/index' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/category/create' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'create',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],
            'admin/category/update' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'update',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],
            'admin/category/delete' => [
                'controller' => \CoreCart\Admin\Controller\CategoryController::class,
                'method'     => 'delete',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],

            // Orders
            'admin/order/index' => [
                'controller' => \CoreCart\Admin\Controller\OrderController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/order/view' => [
                'controller' => \CoreCart\Admin\Controller\OrderController::class,
                'method'     => 'view',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/order/updateStatus' => [
                'controller' => \CoreCart\Admin\Controller\OrderController::class,
                'method'     => 'updateStatus',
                'middleware'  => $authenticatedMutation,
                'methods'    => ['POST'],
            ],

            // Customers
            'admin/customer/index' => [
                'controller' => \CoreCart\Admin\Controller\CustomerController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
            'admin/customer/view' => [
                'controller' => \CoreCart\Admin\Controller\CustomerController::class,
                'method'     => 'view',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],

            // Settings
            'admin/setting/index' => [
                'controller' => \CoreCart\Admin\Controller\SettingController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],

            // Modifications
            'admin/modification/index' => [
                'controller' => \CoreCart\Admin\Controller\ModificationController::class,
                'method'     => 'index',
                'middleware'  => $authenticated,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
