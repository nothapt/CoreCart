<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Infrastructure\SessionInterface;
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

        // Rate limiting for customer login
        $rateLimiter = $this->container->get(\CoreCart\System\Engine\RateLimiter::class);
        $ipAddress = $request->getIpAddress();
        if ($rateLimiter->isLimited($ipAddress, $email)) {
            $remaining = $rateLimiter->getRemainingSeconds($ipAddress);
            return JsonResponse::error("Too many login attempts. Try again in {$remaining} seconds", 429);
        }

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        // Save guest session ID BEFORE login (before regenerate)
        $guestSessionId = $session->getId();

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $user = $customerService->login($email, $password);

        if (!$user) {
            $rateLimiter->recordFailure($ipAddress, $email);
            return JsonResponse::error('Invalid credentials', 401);
        }

        $rateLimiter->recordSuccess($ipAddress, $email);

        // Regenerate session to prevent fixation
        $session->regenerate();

        $session->set('customer_id', (int) $user['customer_id']);
        $session->set('customer_username', $user['username']);
        $session->set('customer_email', $user['email']);

        // Merge guest cart using the OLD session ID (before regenerate)
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cartService->mergeGuestToCustomer($guestSessionId, (int) $user['customer_id']);

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

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);

            // Save guest session ID BEFORE regeneration
            $guestSessionId = $session->getId();

            $session->regenerate();

            $session->set('customer_id', $id);
            $session->set('customer_username', $dto->username);
            $session->set('customer_email', $dto->email);

            // Merge guest cart using the OLD session ID
            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartService->mergeGuestToCustomer($guestSessionId, $id);

            return JsonResponse::success(['customer_id' => $id], 'Registration successful', 201);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 409);
        }
    }

    public function logout(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        $session->invalidate();

        return JsonResponse::success(null, 'Logged out');
    }
}
