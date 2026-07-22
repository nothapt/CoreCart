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
                'controller' => \CoreCart\Api\Controller\ProductController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'api/product/view' => [
                'controller' => \CoreCart\Api\Controller\ProductController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'api/product/search' => [
                'controller' => \CoreCart\Api\Controller\ProductController::class,
                'method'     => 'search',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Categories (public)
            'api/category' => [
                'controller' => \CoreCart\Api\Controller\CategoryController::class,
                'method'     => 'index',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],
            'api/category/view' => [
                'controller' => \CoreCart\Api\Controller\CategoryController::class,
                'method'     => 'view',
                'middleware'  => $public,
                'methods'    => ['GET'],
            ],

            // Cart (optional customer auth, CSRF on mutations)
            'api/cart' => [
                'controller' => \CoreCart\Api\Controller\CartController::class,
                'method'     => 'index',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],
            'api/cart/add' => [
                'controller' => \CoreCart\Api\Controller\CartController::class,
                'method'     => 'add',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/update' => [
                'controller' => \CoreCart\Api\Controller\CartController::class,
                'method'     => 'update',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/remove' => [
                'controller' => \CoreCart\Api\Controller\CartController::class,
                'method'     => 'remove',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/clear' => [
                'controller' => \CoreCart\Api\Controller\CartController::class,
                'method'     => 'clear',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/cart/count' => [
                'controller' => \CoreCart\Api\Controller\CartController::class,
                'method'     => 'count',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],
            'api/csrf-token' => [
                'controller' => \CoreCart\Cart\Controller\CartController::class,
                'method'     => 'csrfToken',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],

            // Checkout
            'api/checkout' => [
                'controller' => \CoreCart\Api\Controller\CheckoutController::class,
                'method'     => 'index',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],
            'api/checkout/confirm' => [
                'controller' => \CoreCart\Api\Controller\CheckoutController::class,
                'method'     => 'confirm',
                'middleware'  => $optionalCustomerMutation,
                'methods'    => ['POST'],
            ],
            'api/checkout/success' => [
                'controller' => \CoreCart\Api\Controller\CheckoutController::class,
                'method'     => 'success',
                'middleware'  => $optionalCustomer,
                'methods'    => ['GET'],
            ],

            // Account (authenticated)
            'api/account/profile' => [
                'controller' => \CoreCart\Api\Controller\AccountController::class,
                'method'     => 'profile',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'api/account/password' => [
                'controller' => \CoreCart\Api\Controller\AccountController::class,
                'method'     => 'password',
                'middleware'  => $customerMutation,
                'methods'    => ['POST'],
            ],
            'api/account/order' => [
                'controller' => \CoreCart\Api\Controller\AccountController::class,
                'method'     => 'orders',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'api/account/address' => [
                'controller' => \CoreCart\Api\Controller\AddressController::class,
                'method'     => 'index',
                'middleware'  => $customerAuth,
                'methods'    => ['GET'],
            ],
            'api/account/address/create' => [
                'controller' => \CoreCart\Api\Controller\AddressController::class,
                'method'     => 'create',
                'middleware'  => $customerMutation,
                'methods'    => ['POST'],
            ],
        ]);
    }
}
