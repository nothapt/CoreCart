<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class CartRouteProvider
{
    public function register(Router $router): void
    {
        $public = [
            \CoreCart\System\Engine\SecurityHeaders::class,
        ];

        $optionalAuth = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
        ];

        $withRequest = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        $router->addRoutes([
            'cart' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'index',
                'middleware'  => $optionalAuth,
                'methods'    => ['GET'],
            ],
            'cart/add' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'add',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            'cart/update' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'update',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            'cart/remove' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'remove',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            'cart/clear' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'clear',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            'cart/count' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'count',
                'middleware'  => $optionalAuth,
                'methods'    => ['GET'],
            ],
            'cart/csrf-token' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'csrfToken',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
        ]);
    }
}