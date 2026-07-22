<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class CheckoutRouteProvider
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

        $router->addRoutes([
            'checkout' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'index',
                'middleware'  => $optionalAuth,
                'methods'    => ['GET'],
            ],
            'checkout/confirm' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'confirm',
                'middleware'  => $withCsrf,
                'methods'    => ['POST'],
            ],
            'checkout/success' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'success',
                'middleware'  => $optionalAuth,
                'methods'    => ['GET'],
            ],
            'checkout/csrf-token' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'csrfToken',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
        ]);
    }
}