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

        $auth = [
            \CoreCart\System\Engine\SecurityHeaders::class,
        ];

        $router->addRoutes([
            // Auth
            'account/login' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'login',
                'middleware'  => $public,
                'methods'    => ['GET', 'POST'],
            ],
            'account/register' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'register',
                'middleware'  => $public,
                'methods'    => ['GET', 'POST'],
            ],
            'account/logout' => [
                'controller' => \CoreCart\Account\Controller\AuthController::class,
                'method'     => 'logout',
                'middleware'  => $public,
                'methods'    => ['POST'],
            ],

            // Profile
            'account/profile' => [
                'controller' => \CoreCart\Account\Controller\AccountController::class,
                'method'     => 'profile',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'account/password' => [
                'controller' => \CoreCart\Account\Controller\AccountController::class,
                'method'     => 'password',
                'middleware'  => $auth,
                'methods'    => ['POST'],
            ],

            // Addresses
            'account/address' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'account/address/create' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'create',
                'middleware'  => $auth,
                'methods'    => ['GET', 'POST'],
            ],
            'account/address/edit' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'edit',
                'middleware'  => $auth,
                'methods'    => ['GET', 'POST'],
            ],
            'account/address/delete' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'delete',
                'middleware'  => $auth,
                'methods'    => ['POST'],
            ],

            // Orders
            'account/order' => [
                'controller' => \CoreCart\Account\Controller\OrderController::class,
                'method'     => 'index',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
            'account/order/view' => [
                'controller' => \CoreCart\Account\Controller\OrderController::class,
                'method'     => 'view',
                'middleware'  => $auth,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
