<?php
declare(strict_types=1);

namespace CoreCart\Checkout\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Dto\OrderCreateDTO;

class CheckoutController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $sessionId = session_id();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cart = $cartService->getCart($sessionId);

        if (empty($cart['items'])) {
            return JsonResponse::error('Cart is empty', 400);
        }

        return JsonResponse::success([
            'cart'   => $cart,
            'total'  => $cart['total'],
        ]);
    }

    public function confirm(Request $request): Response
    {
        $sessionId = session_id();
        $customerId = $_SESSION['customer_id'] ?? null;
        $comment = $request->getInput('comment', '');

        $dto = new OrderCreateDTO(
            customerId: $customerId ? (int) $customerId : null,
            comment: $comment
        );

        try {
            $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);

            if ($customerId) {
                $orderId = $orderService->createOrderFromCart((int) $customerId, $dto);
            } else {
                $orderId = $orderService->createOrder($sessionId, $dto);
            }

            return JsonResponse::success(
                ['order_id' => $orderId],
                'Order placed successfully',
                201
            );
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    public function success(Request $request): Response
    {
        $orderId = (int) $request->getQueryParam('order_id', 0);
        if ($orderId <= 0) {
            return new JsonResponse(['message' => 'Order placed. Thank you!']);
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($orderId);

        if (!$order) {
            return JsonResponse::error('Order not found', 404);
        }

        return JsonResponse::success([
            'order_id' => $order->id,
            'total'    => $order->total,
            'status'   => $order->status,
        ], 'Order placed successfully');
    }
}
