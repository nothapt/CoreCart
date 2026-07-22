<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class AccountController
{
    public function __construct(
        private Container $container,
    ) {}

    public function profile(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $customer = $customerService->getCustomer($customerId);

        if (!$customer) {
            return new HtmlResponse('Customer not found', 404);
        }

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['customer'] = $customer;

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('account/profile', $data));
    }

    public function password(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        $password = $request->getInput('password', '');
        if (strlen($password) < 6) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', 'Password must be at least 6 characters');
            return new RedirectResponse('/account/profile');
        }

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->changePassword($customerId, $password);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Password updated');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/account/profile');
    }
}
