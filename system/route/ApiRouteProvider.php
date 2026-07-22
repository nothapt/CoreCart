<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class ApiRouteProvider
{
    public function register(Router $router): void
    {
        $public = [
            \CoreCart\System\Engine\SecurityHeaders::class,
        ];

        $optionalCustomer = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
        ];

        $optionalCustomerMutation = [
            \CoreCart\System\Engine\SecurityHeaders::class,
            \CoreCart\System\Engine\OptionalCustomerAuthMiddleware::class,
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
            // Products (public)
            'api/product' => [
                'controller' => \CoreCart\Catalog\Controller\ProductController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'api/product/view' => [
                'controller' => \CoreCart\Catalog\Controller\ProductController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'api/product/search' => [
                'controller' => \CoreCart\Catalog\Controller\ProductController::class,
                'method'     => 'search',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Categories (public)
            'api/category' => [
                'controller' => \CoreCart\Catalog\Controller\CategoryController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'api/category/view' => [
                'controller' => \CoreCart\Catalog\Controller\CategoryController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Cart (optional customer auth, CSRF on mutations)
            'api/cart' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'index',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],
            'api/cart/add' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'add',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/update' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'update',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/remove' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'remove',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/clear' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'clear',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/count' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'count',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],

            // Checkout (optional customer auth, CSRF on mutations)
            'api/checkout' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'index',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],
            'api/checkout/confirm' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'confirm',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/checkout/success' => [
                'controller' => \CoreCart\Checkout\Controller\CheckoutController::class,
                'method'     => 'success',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],

            // Security
            'api/csrf-token' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'csrfToken',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],

            // Customer account (authenticated, CSRF on mutations)
            'api/account/profile' => [
                'controller' => \CoreCart\Account\Controller\AccountController::class,
                'method'     => 'profile',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'api/account/password' => [
                'controller' => \CoreCart\Account\Controller\AccountController::class,
                'method'     => 'password',
                'middleware'  => $customerMutation,
                'methods'    => ['POST'],
            ],
            'api/account/address' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'index',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'api/account/address/create' => [
                'controller' => \CoreCart\Account\Controller\AddressController::class,
                'method'     => 'create',
                'middleware'  => $customerMutation,
                'methods'    => ['POST'],
            ],
            'api/account/order' => [
                'controller' => \CoreCart\Account\Controller\OrderController::class,
                'method'     => 'index',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'api/account/order/view' => [
                'controller' => \CoreCart\Account\Controller\OrderController::class,
                'method'     => 'view',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
        ]);
    }
}
