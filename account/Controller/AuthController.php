<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Dto\RegisterDTO;

class AuthController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function login(Request $request): Response
    {
        if ($request->isGet()) {
            return new JsonResponse(['message' => 'Login form']);
        }

        $email = trim($request->getInput('email', ''));
        $password = $request->getInput('password', '');

        if ($email === '' || $password === '') {
            return JsonResponse::error('Email and password are required', 422);
        }

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $user = $customerService->login($email, $password);

        if (!$user) {
            return JsonResponse::error('Invalid credentials', 401);
        }

        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int) $user['customer_id'];
        $_SESSION['customer_username'] = $user['username'];
        $_SESSION['customer_email'] = $user['email'];

        // Merge guest cart
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cartService->mergeGuestToCustomer(session_id(), (int) $user['customer_id']);

        return JsonResponse::success([
            'id'       => (int) $user['customer_id'],
            'username' => $user['username'],
            'email'    => $user['email'],
        ], 'Login successful');
    }

    public function register(Request $request): Response
    {
        if ($request->isGet()) {
            return new JsonResponse(['message' => 'Register form']);
        }

        $dto = RegisterDTO::fromArray($request->getBody());

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $id = $customerService->register($dto);

            session_regenerate_id(true);
            $_SESSION['customer_id'] = $id;
            $_SESSION['customer_username'] = $dto->username;
            $_SESSION['customer_email'] = $dto->email;

            return JsonResponse::success(['customer_id' => $id], 'Registration successful', 201);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 409);
        }
    }

    public function logout(Request $request): Response
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();

        return JsonResponse::success(null, 'Logged out');
    }
}
