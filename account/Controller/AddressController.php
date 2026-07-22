<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Dto\AddressDTO;

class AddressController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $addresses = $customerService->getAddresses($customerId);

        $context = $this->container->get(\CoreCart\System\View\StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['addresses'] = $addresses;

        $renderer = $this->container->get(\CoreCart\System\View\TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('account/profile.html.twig', $data));
    }

    public function create(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        if ($request->isGet()) {
            $context = $this->container->get(\CoreCart\System\View\StorefrontContextProvider::class);
            $data = $context->build($request);
            $renderer = $this->container->get(\CoreCart\System\View\TemplateRendererInterface::class);
            return new HtmlResponse($renderer->render('account/profile.html.twig', $data));
        }

        $dto = AddressDTO::fromArray($request->getBody());

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->addAddress($customerId, $dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Address created');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/account/profile');
    }

    public function edit(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        $addressId = (int) $request->getQueryParam('id', 0);
        if ($addressId <= 0) {
            return new RedirectResponse('/account/profile');
        }

        if ($request->isGet()) {
            $context = $this->container->get(\CoreCart\System\View\StorefrontContextProvider::class);
            $data = $context->build($request);
            $renderer = $this->container->get(\CoreCart\System\View\TemplateRendererInterface::class);
            return new HtmlResponse($renderer->render('account/profile.html.twig', $data));
        }

        $dto = AddressDTO::fromArray($request->getBody());

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->updateAddress($customerId, $addressId, $dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Address updated');
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/account/profile');
    }

    public function delete(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        $addressId = (int) $request->getInput('address_id', 0);
        if ($addressId <= 0) {
            return new RedirectResponse('/account/profile');
        }

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->deleteAddress($customerId, $addressId);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Address deleted');
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/account/profile');
    }
}
