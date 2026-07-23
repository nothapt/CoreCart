<?php
declare(strict_types=1);

namespace CoreCart\Api\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Service\CartService;
use CoreCart\System\Service\OrderService;

final class CheckoutController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $cartData = $cart->getCart($sessionId, $customerId);

        return JsonResponse::success([
            'items' => $cartData['items'],
            'total' => $cartData['total'],
        ]);
    }

    public function confirm(Request $request): Response
    {
        $body = $request->getBody();
        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();

        $dto = new \CoreCart\System\Dto\OrderCreateDTO(
            customerId: $customerId,
            customerEmail: $body['email'] ?? '',
            customerPhone: $body['phone'] ?? null,
            shippingFirstname: $body['firstname'] ?? null,
            shippingLastname: $body['lastname'] ?? null,
            shippingAddress1: $body['address_1'] ?? null,
            shippingAddress2: $body['address_2'] ?? null,
            shippingCity: $body['city'] ?? null,
            shippingPostcode: $body['postcode'] ?? null,
            shippingCountry: $body['country'] ?? null,
            shippingZone: $body['zone'] ?? null,
            comment: $body['comment'] ?? null,
        );

        $validator = new \CoreCart\System\Validation\CheckoutValidator();
        if (!$validator->validate($dto)) {
            return JsonResponse::error($validator->getFirstError(), 422);
        }

        $orderService = $this->container->get(OrderService::class);

        try {
            $orderId = $customerId
                ? $orderService->createOrderFromCart($customerId, $dto)
                : $orderService->createOrder($sessionId, $dto);
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }

        return JsonResponse::success(['order_id' => $orderId], 'Order placed', 201);
    }

    public function success(Request $request): Response
    {
        $orderId = (int) ($request->getQueryParam('order_id') ?? 0);
        if ($orderId <= 0) {
            return JsonResponse::error('Order ID is required', 400);
        }

        $orderService = $this->container->get(OrderService::class);
        $order = $orderService->getOrder($orderId);

        if (!$order) {
            return JsonResponse::error('Order not found', 404);
        }

        return JsonResponse::success($order);
    }
}
