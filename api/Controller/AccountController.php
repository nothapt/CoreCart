<?php
declare(strict_types=1);

namespace CoreCart\Api\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Service\CustomerService;
use CoreCart\System\Service\OrderService;

final class AccountController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function profile(Request $request): Response
    {
        $customerId = $request->getUserId();
        if ($customerId === null) {
            return JsonResponse::error('Authentication required', 401);
        }

        $customerService = $this->container->get(CustomerService::class);
        $customer = $customerService->getCustomer($customerId);

        if (!$customer) {
            return JsonResponse::error('Customer not found', 404);
        }

        return JsonResponse::success($customer);
    }

    public function password(Request $request): Response
    {
        $customerId = $request->getUserId();
        if ($customerId === null) {
            return JsonResponse::error('Authentication required', 401);
        }

        $body = $request->getBody();
        $customerService = $this->container->get(CustomerService::class);

        try {
            $customerService->changePassword(
                $customerId,
                $body['new_password'] ?? '',
            );
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }

        return JsonResponse::success(null, 'Password changed');
    }

    public function orders(Request $request): Response
    {
        $customerId = $request->getUserId();
        if ($customerId === null) {
            return JsonResponse::error('Authentication required', 401);
        }

        $orderService = $this->container->get(OrderService::class);
        $page = max(1, (int) ($request->getQueryParam('page') ?? 1));
        $orders = $orderService->getCustomerOrders($customerId, $page);

        return JsonResponse::success($orders);
    }
}
