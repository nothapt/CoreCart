<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Infrastructure\OrderStatus;

class OrderController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $statusParam = $request->getQueryParam('status');
        $status = null;
        if ($statusParam !== null) {
            try {
                $status = OrderStatus::fromInt((int) $statusParam);
            } catch (\InvalidArgumentException $e) {
                return JsonResponse::error('Invalid status value', 422, 'VALIDATION_ERROR');
            }
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $data = $orderService->getOrders($page, 20, $status);

        return JsonResponse::success($data);
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return JsonResponse::error('Invalid order ID', 400);
        }

        $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
        $order = $orderService->getOrder($id);

        if (!$order) {
            return JsonResponse::error('Order not found', 404);
        }

        return JsonResponse::success($order->toArray());
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->getInput('order_id', $request->getQueryParam('id', 0));
        $statusValue = (int) $request->getInput('status', 0);

        if ($id <= 0) {
            return JsonResponse::error('Invalid order ID', 400);
        }

        try {
            $status = OrderStatus::fromInt($statusValue);
            $orderService = $this->container->get(\CoreCart\System\Service\OrderService::class);
            $orderService->updateStatus($id, $status);
            return JsonResponse::success(null, 'Order status updated');
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }
}
