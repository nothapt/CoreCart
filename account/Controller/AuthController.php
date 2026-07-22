<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Dto\RegisterDTO;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class AuthController
{
    public function __construct(
        private Container $container,
    ) {}

    public function login(Request $request): Response
    {
        if ($request->isLoggedIn()) {
            return new RedirectResponse('/account/profile');
        }

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('account/login.html.twig', $data));
    }

    public function loginPost(Request $request): Response
    {
        $email = trim($request->getInput('email', ''));
        $password = $request->getInput('password', '');

        if ($email === '' || $password === '') {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', 'Email and password are required');
            return new RedirectResponse('/account/login');
        }

        $rateLimiter = $this->container->get(\CoreCart\System\Engine\RateLimiter::class);
        $ipAddress = $request->getIpAddress();
        if ($rateLimiter->isLimited($ipAddress, $email)) {
            $remaining = $rateLimiter->getRemainingSeconds($ipAddress);
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', "Too many login attempts. Try again in {$remaining} seconds");
            return new RedirectResponse('/account/login');
        }

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        $guestSessionId = $session->getId();

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $user = $customerService->login($email, $password);

        if (!$user) {
            $rateLimiter->recordFailure($ipAddress, $email);
            $session->set('flash_error', 'Invalid credentials');
            return new RedirectResponse('/account/login');
        }

        $rateLimiter->recordSuccess($ipAddress, $email);
        $session->regenerate();

        $session->set('customer_id', (int) $user['customer_id']);
        $session->set('customer_username', $user['username']);
        $session->set('customer_email', $user['email']);

        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cartService->mergeGuestToCustomer($guestSessionId, (int) $user['customer_id']);

        return new RedirectResponse('/account/profile');
    }

    public function register(Request $request): Response
    {
        if ($request->isLoggedIn()) {
            return new RedirectResponse('/account/profile');
        }

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('account/register.html.twig', $data));
    }

    public function registerPost(Request $request): Response
    {
        $dto = RegisterDTO::fromArray($request->getBody());

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $id = $customerService->register($dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $guestSessionId = $session->getId();

            $session->regenerate();

            $session->set('customer_id', $id);
            $session->set('customer_username', $dto->username);
            $session->set('customer_email', $dto->email);

            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartService->mergeGuestToCustomer($guestSessionId, $id);

            return new RedirectResponse('/account/profile');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
            return new RedirectResponse('/account/register');
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
            return new RedirectResponse('/account/register');
        }
    }

    public function logout(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        $session->invalidate();

        return new RedirectResponse('/');
    }
}
