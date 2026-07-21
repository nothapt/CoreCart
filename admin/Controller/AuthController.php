<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Dto\LoginDTO;

class AuthController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function login(Request $request): Response
    {
        if ($request->isLoggedIn()) {
            return JsonResponse::success(null, 'Already logged in');
        }

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        return new JsonResponse([
            'message'    => 'Login form',
            'csrf_token' => $session->get('csrf_token', ''),
        ]);
    }

    public function loginPost(Request $request): Response
    {
        $dto = LoginDTO::fromArray($request->getBody());

        $validator = $this->container->get(\CoreCart\System\Engine\Validator::class);
        $validator->validate($request->getBody(), [
            'login'    => 'required|string|min:2|max:255',
            'password' => 'required|string|min:6',
        ]);

        if (!empty($validator->getErrors()['fields'])) {
            return JsonResponse::validationErrors($validator->getErrors()['fields']);
        }

        try {
            $authService = $this->container->get(\CoreCart\System\Service\AuthService::class);
            $user = $authService->loginAdmin($dto, $request->getIpAddress());

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->regenerate();

            $session->set('admin_user_id', $user['id']);
            $session->set('admin_username', $user['username']);
            $session->set('admin_email', $user['email']);
            $session->set('admin_login_time', time());
            $session->set('admin_last_activity', time());
            $session->set('admin_ip', $request->getIpAddress());

            return JsonResponse::success($user, 'Login successful');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $code = str_starts_with($message, 'Too many') ? 429 : 401;
            return JsonResponse::error($message, $code);
        }
    }

    public function logout(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        $session->invalidate();

        $response = JsonResponse::success(null, 'Logged out');
        $response->addCookie(session_name(), '', time() - 42000, '/');
        return $response;
    }

    public function csrfToken(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        if (!$session->has('csrf_token')) {
            $session->set('csrf_token', bin2hex(random_bytes(32)));
        }

        return new JsonResponse(['data' => ['csrf_token' => $session->get('csrf_token')]]);
    }
}
