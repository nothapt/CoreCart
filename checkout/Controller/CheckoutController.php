<?php
declare(strict_types=1);

namespace CoreCart\Checkout\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\Infrastructure\SessionInterface;

class CheckoutController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cart = $cartService->getCart($sessionId, $customerId);

        if (empty($cart['items'])) {
            return JsonResponse::error('Cart is empty', 400);
        }

        return JsonResponse::success([
            'cart'  => $cart,
            'total' => $cart['total'],
        ]);
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

            // Store order_id in session for the success page verification
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('last_order_id', $orderId);

            return JsonResponse::success(
                ['order_id' => $orderId],
                'Order placed successfully',
                201
            );
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Success page — verifies the order belongs to the current user/session.
     */
    public function success(Request $request): Response
    {
        $orderId = (int) $request->getQueryParam('order_id', 0);

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        // Verify order belongs to this user
        $lastOrderId = (int) $session->get('last_order_id', 0);

        if ($orderId !== $lastOrderId) {
            return JsonResponse::error('Order not found', 404);
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($orderId);

        if (!$order) {
            return JsonResponse::error('Order not found', 404);
        }

        // Verify ownership for logged-in users
        $userId = $request->getUserId();
        if ($userId && $order->customerId !== $userId) {
            return JsonResponse::error('Order not found', 404);
        }

        // Clear the last_order_id to prevent reuse
        $session->remove('last_order_id');

        return JsonResponse::success([
            'order_id' => $order->id,
            'total'    => $order->total,
            'status'   => $order->status,
        ], 'Order placed successfully');
    }

    private function getSessionId(): string
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        return $session->getId();
    }

    public function csrfToken(Request $request): Response
    {
        /** @var \CoreCart\System\Engine\CsrfMiddleware $csrfMiddleware */
        $csrfMiddleware = $this->container->get(\CoreCart\System\Engine\CsrfMiddleware::class);
        return new JsonResponse(['data' => ['csrf_token' => $csrfMiddleware->getToken()]]);
    }
}
