<?php
declare(strict_types=1);

namespace CoreCart\Checkout\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class CheckoutController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cart = $cartService->getCart($sessionId, $customerId);

        if (empty($cart['items'])) {
            return new RedirectResponse('/cart');
        }

        // Pre-fill customer data if logged in
        $customer = null;
        if ($customerId) {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customer = $customerService->getCustomer($customerId);
        }

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['items'] = $cart['items'] ?? [];
        $data['total'] = $cart['total'] ?? '0.00';
        $data['customer'] = $customer;

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('checkout/index.html.twig', $data));
    }

    public function confirm(Request $request): Response
    {
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();

        $dto = OrderCreateDTO::fromArray(array_merge(
            $request->getBody(),
            ['customer_id' => $customerId]
        ));

        try {
            $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);

            if ($customerId) {
                $orderId = $orderService->createOrderFromCart($customerId, $dto);
            } else {
                $orderId = $orderService->createOrder($sessionId, $dto);
            }

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('last_order_id', $orderId);

            return new RedirectResponse('/checkout/success?order_id=' . $orderId);
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
            return new RedirectResponse('/checkout');
        }
    }

    public function success(Request $request): Response
    {
        $orderId = (int) $request->getQueryParam('order_id', 0);

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $lastOrderId = (int) $session->get('last_order_id', 0);

        if ($orderId !== $lastOrderId) {
            return new HtmlResponse('Order not found', 404);
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($orderId);

        if (!$order) {
            return new HtmlResponse('Order not found', 404);
        }

        $userId = $request->getUserId();
        if ($userId && $order->customerId !== $userId) {
            return new HtmlResponse('Order not found', 403);
        }

        $session->remove('last_order_id');

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['order'] = $order;

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('checkout/success.html.twig', $data));
    }

    public function csrfToken(Request $request): Response
    {
        /** @var \CoreCart\System\Engine\CsrfMiddleware $csrfMiddleware */
        $csrfMiddleware = $this->container->get(\CoreCart\System\Engine\CsrfMiddleware::class);
        return new \CoreCart\System\Engine\JsonResponse(['data' => ['csrf_token' => $csrfMiddleware->getToken()]]);
    }

    private function getSessionId(): string
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        return $session->getId();
    }
}
