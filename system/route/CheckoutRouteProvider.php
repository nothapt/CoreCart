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

        $publicWithCsrf = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\CsrfMiddleware::class,
        ];

        $router->addRoutes([
            'checkout' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'checkout/confirm' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'confirm',
                'middleware'  => $publicWithCsrf,
                'methods'    => ['POST'],
            ],
            'checkout/success' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'success',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
