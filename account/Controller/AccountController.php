<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;

class AccountController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function profile(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $customer = $customerService->getCustomer((int) $customerId);

        if (!$customer) {
            return JsonResponse::error('Customer not found', 404);
        }

        $addresses = $customerService->getAddresses((int) $customerId);

        return JsonResponse::success([
            'customer'  => $customer->toArray(),
            'addresses' => array_map(fn($a) => $a->toArray(), $addresses),
        ]);
    }

    public function password(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        $password = $request->getInput('password', '');
        if (strlen($password) < 6) {
            return JsonResponse::error('Password must be at least 6 characters', 422);
        }

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->changePassword((int) $customerId, $password);
            return JsonResponse::success(null, 'Password updated');
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        }
    }
}
