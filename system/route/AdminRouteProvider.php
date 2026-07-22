<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\AuthMiddleware;
use CoreCart\System\Engine\CsrfMiddleware;
use CoreCart\System\Engine\RequestMiddleware;
use CoreCart\System\Engine\Router;
use CoreCart\System\Engine\SecurityHeaders;

final class AdminRouteProvider
{
    public function register(Router $router): void
    {
        $publicForm = [
            SecurityHeaders::class,
            CsrfMiddleware::class,
        ];

        $publicMutation = [
            SecurityHeaders::class,
            CsrfMiddleware::class,
            RequestMiddleware::class,
        ];

        $authenticated = [
            SecurityHeaders::class,
            AuthMiddleware::class,
        ];

        $authenticatedMutation = [
            SecurityHeaders::class,
            AuthMiddleware::class,
            CsrfMiddleware::class,
            RequestMiddleware::class,
        ];

        // Authentication
        $router->addRoute('/admin', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/login', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/login', \CoreCart\Admin\Controller\AuthController::class, 'loginPost', $publicMutation, ['POST']);
        $router->addRoute('/admin/auth/login', \CoreCart\Admin\Controller\AuthController::class, 'login', $publicForm, ['GET']);
        $router->addRoute('/admin/auth/loginPost', \CoreCart\Admin\Controller\AuthController::class, 'loginPost', $publicMutation, ['POST']);
        $router->addRoute('/admin/auth/logout', \CoreCart\Admin\Controller\AuthController::class, 'logout', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/csrf-token', \CoreCart\Admin\Controller\AuthController::class, 'csrfToken', $authenticated, ['GET']);

        // Dashboard
        $router->addRoute('/admin/dashboard', \CoreCart\Admin\Controller\DashboardController::class, 'index', $authenticated, ['GET']);

        // Products
        $router->addRoute('/admin/product/index', \CoreCart\Admin\Controller\ProductController::class, 'index', $authenticated, ['GET']);
        $router->addRoute('/admin/product/create', \CoreCart\Admin\Controller\ProductController::class, 'create', $authenticated, ['GET']);
        $router->addRoute('/admin/product/create', \CoreCart\Admin\Controller\ProductController::class, 'createPost', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/product/createPost', \CoreCart\Admin\Controller\ProductController::class, 'createPost', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/product/edit', \CoreCart\Admin\Controller\ProductController::class, 'edit', $authenticated, ['GET']);
        $router->addRoute('/admin/product/update', \CoreCart\Admin\Controller\ProductController::class, 'update', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/product/delete', \CoreCart\Admin\Controller\ProductController::class, 'delete', $authenticatedMutation, ['POST']);

        // Categories
        $router->addRoute('/admin/category/index', \CoreCart\Admin\Controller\CategoryController::class, 'index', $authenticated, ['GET']);
        $router->addRoute('/admin/category/create', \CoreCart\Admin\Controller\CategoryController::class, 'createForm', $authenticated, ['GET']);
        $router->addRoute('/admin/category/create', \CoreCart\Admin\Controller\CategoryController::class, 'create', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/category/edit', \CoreCart\Admin\Controller\CategoryController::class, 'editForm', $authenticated, ['GET']);
        $router->addRoute('/admin/category/edit', \CoreCart\Admin\Controller\CategoryController::class, 'update', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/category/update', \CoreCart\Admin\Controller\CategoryController::class, 'update', $authenticatedMutation, ['POST']);
        $router->addRoute('/admin/category/delete', \CoreCart\Admin\Controller\CategoryController::class, 'delete', $authenticatedMutation, ['POST']);

        // Orders
        $router->addRoute('/admin/order/index', \CoreCart\Admin\Controller\OrderController::class, 'index', $authenticated, ['GET']);
        $router->addRoute('/admin/order/view', \CoreCart\Admin\Controller\OrderController::class, 'view', $authenticated, ['GET']);
        $router->addRoute('/admin/order/updateStatus', \CoreCart\Admin\Controller\OrderController::class, 'updateStatus', $authenticatedMutation, ['POST']);

        // Customers
        $router->addRoute('/admin/customer/index', \CoreCart\Admin\Controller\CustomerController::class, 'index', $authenticated, ['GET']);
        $router->addRoute('/admin/customer/view', \CoreCart\Admin\Controller\CustomerController::class, 'view', $authenticated, ['GET']);

        // Design
        $router->addRoute('/admin/design/theme-editor', \CoreCart\Admin\Controller\ThemeEditorController::class, 'index', $authenticated, ['GET']);
        $router->addRoute('/admin/design/theme-editor', \CoreCart\Admin\Controller\ThemeEditorController::class, 'save', $authenticatedMutation, ['POST']);

        // System
        $router->addRoute('/admin/setting/index', \CoreCart\Admin\Controller\SettingController::class, 'index', $authenticated, ['GET']);
        $router->addRoute('/admin/modification/index', \CoreCart\Admin\Controller\ModificationController::class, 'index', $authenticated, ['GET']);
    }
}