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

        $withCsrf = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
        ];

        $withRequest = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
            \CoreCart\System\Engine\RequestMiddleware::class,
        ];

        $router->addRoutes([
            // View cart (GET, HTML)
            'cart' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'index',
                'middleware'  => $optionalAuth,
                'methods'    => ['GET'],
            ],
            // Add to cart (POST, redirect back)
            'cart/add' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'add',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            // Update quantity (POST, redirect back)
            'cart/update' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'update',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            // Remove item (POST, redirect back)
            'cart/remove' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'remove',
                'middleware'  => $withRequest,
                'methods'    => ['POST'],
            ],
            // Clear cart (POST, redirect back)
            'cart/clear' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'clear',
                'middleware'  => $withCsrf,
                'methods'    => ['POST'],
            ],
            // Cart count (GET, JSON - for AJAX)
            'cart/count' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'count',
                'middleware'  => $optionalAuth,
                'methods'    => ['GET'],
            ],
            // CSRF token (GET, JSON)
            'cart/csrf-token' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'csrfToken',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
