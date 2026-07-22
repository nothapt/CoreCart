<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Dto\LoginDTO;
use CoreCart\System\View\TemplateRendererInterface;

class AuthController
{
    public function __construct(
        private Container $container,
    ) {}

    public function login(Request $request): Response
    {
        if ($request->isLoggedIn()) {
            return new RedirectResponse('/admin/dashboard');
        }

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $data = [
            'csrf_token' => $session->get('csrf_token', ''),
            'shop_name'  => 'CoreCart',
        ];

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('auth/login', $data));
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
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', 'Please fill in all fields correctly');
            return new RedirectResponse('/admin/auth/login');
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

            return new RedirectResponse('/admin/dashboard');
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
            return new RedirectResponse('/admin/auth/login');
        }
    }

    public function logout(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        $session->invalidate();

        $response = new RedirectResponse('/admin/auth/login');
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

        return new \CoreCart\System\Engine\JsonResponse(['data' => ['csrf_token' => $session->get('csrf_token')]]);
    }
}
