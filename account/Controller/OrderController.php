<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;

class OrderController
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

        $page = max(1, (int) $request->getQueryParam('page', 1));
        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $data = $orderService->getCustomerOrders($customerId, $page);

        $context = $this->container->get(\CoreCart\System\View\StorefrontContextProvider::class);
        $ctx = $context->build($request);
        $ctx['orders'] = $data['orders'] ?? [];
        $ctx['total'] = $data['total'] ?? 0;
        $ctx['page'] = $data['page'] ?? 1;
        $ctx['pages'] = $data['pages'] ?? 1;

        $renderer = $this->container->get(\CoreCart\System\View\TemplateRendererInterface::class);
        return new \CoreCart\System\Engine\HtmlResponse($renderer->render('account/profile.html.twig', $ctx));
    }

    public function view(Request $request): Response
    {
        $customerId = $request->getUserId();
        if (!$customerId) {
            return new RedirectResponse('/account/login');
        }

        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/account/profile');
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($id);

        if (!$order || $order->customerId !== $customerId) {
            return new RedirectResponse('/account/profile');
        }

        $context = $this->container->get(\CoreCart\System\View\StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['order'] = $order;

        $renderer = $this->container->get(\CoreCart\System\View\TemplateRendererInterface::class);
        return new \CoreCart\System\Engine\HtmlResponse($renderer->render('account/profile.html.twig', $data));
    }
}
