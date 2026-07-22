<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class AccountRouteProvider
{
    public function register(Router $router): void
    {
        $public = [
            \CoreCart\System\Engine\SecurityHeaders::class,
        ];

        $publicWithCsrf = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
        ];

        $publicMutation = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        $customerAuth = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CustomerAuthMiddleware::class,
        ];

        $customerMutation = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CustomerAuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        $router->addRoutes([
            // Auth (public)
            'account/login' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'login',
                'middleware'  => $publicWithCsrf,
                'methods'    => ['GET'],
            ],
            'account/loginPost' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'loginPost',
                'middleware'  => $publicMutation,
                'methods'    => ['POST'],
            ],
            'account/register' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'register',
                'middleware'  => $publicWithCsrf,
                'methods'    => ['GET'],
            ],
            'account/registerPost' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'registerPost',
                'middleware'  => $publicMutation,
                'methods'    => ['POST'],
            ],
            'account/logout' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'logout',
                'middleware'  => $publicWithCsrf,
                'methods'    => ['POST'],
            ],

            // Profile (authenticated)
            'account/profile' => [
                'controller' => \CoreCart\Account\Controller\AccountController::class,
                'method'     => 'profile',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'account/password' => [
                'controller' => \CoreCart\Account\Controller\AccountController::class,
                'method'     => 'password',
                'middleware'  => $customerMutation,
                'methods'    => ['POST'],
            ],

            // Addresses (authenticated, CSRF on mutations)
            'account/address' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'index',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'account/address/create' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'create',
                'middleware'  => $customerMutation,
                'methods'    => ['GET', 'POST'],
            ],
            'account/address/edit' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'edit',
                'middleware'  => $customerMutation,
                'methods'    => ['GET', 'POST'],
            ],
            'account/address/delete' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'delete',
                'middleware'  => $customerMutation,
                'methods'    => ['POST'],
            ],

            // Orders (authenticated)
            'account/order' => [
                'controller' => \CoreCart\Account\Controller\OrderController::class,
                'method'     => 'index',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'account/order/view' => [
                'controller' => \CoreCart\Account\Controller\OrderController::class,
                'method'     => 'view',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
