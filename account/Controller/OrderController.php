<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;

class OrderController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        $page = max(1, (int) $request->getQueryParam('page', 1));
        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $data = $orderService->getCustomerOrders((int) $customerId, $page);

        return JsonResponse::success($data);
    }

    public function view(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return JsonResponse::error('Invalid order ID', 400);
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($id);

        if (!$order) {
            return JsonResponse::error('Order not found', 404);
        }

        if ($order->customerId !== (int) $customerId) {
            return JsonResponse::error('Access denied', 403);
        }

        return JsonResponse::success($order->toArray());
    }
}
