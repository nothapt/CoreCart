<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\OrderStatus;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\View\AdminContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class OrderController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $statusParam = $request->getQueryParam('status');
        $status = null;
        if ($statusParam !== null) {
            try {
                $status = OrderStatus::fromInt((int) $statusParam);
            } catch (\InvalidArgumentException $e) {
                // ignore
            }
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $data = $orderService->getOrders($page, 20, $status);

        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['orders'] = $data['orders'] ?? [];
        $ctx['total'] = $data['total'] ?? 0;
        $ctx['page'] = $data['page'] ?? 1;
        $ctx['pages'] = $data['pages'] ?? 1;
        $ctx['active_menu'] = 'order';

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('order/list.html.twig', $ctx));
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/order/index');
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($id);

        if (!$order) {
            return new RedirectResponse('/admin/order/index');
        }

        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['order'] = $order;
        $ctx['order_history'] = $orderService->getHistory($id);
        $ctx['active_menu'] = 'order';

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('order/view.html.twig', $ctx));
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->getInput('order_id', 0);
        $statusValue = (int) $request->getInput('status', 0);
        $comment = $request->getInput('comment', '');

        if ($id <= 0) {
            return new RedirectResponse('/admin/order/index');
        }

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        $csrfToken = $request->getInput('_csrf_token', '');
        if ($csrfToken === '' || $csrfToken !== $session->get('csrf_token', '')) {
            $session->set('flash_error', 'Invalid CSRF token');
            return new RedirectResponse('/admin/order/view?id=' . $id);
        }

        try {
            $status = OrderStatus::fromInt($statusValue);
            $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
            $adminUserId = (int) $session->get('admin_user_id', 0);
            $orderService->transitionStatus($id, $status, $comment, $adminUserId ?: null);

            $session->set('flash_success', 'Order status updated');
        } catch (\InvalidArgumentException $e) {
            $session->set('flash_error', $e->getMessage());
        } catch (\RuntimeException $e) {
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/order/view?id=' . $id);
    }
}
