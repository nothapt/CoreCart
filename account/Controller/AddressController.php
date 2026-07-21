<?php
declare(strict_types=1);

namespace CoreCart\Account\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Dto\AddressDTO;

class AddressController
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

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $addresses = $customerService->getAddresses((int) $customerId);

        return JsonResponse::success(array_map(fn($a) => $a->toArray(), $addresses));
    }

    public function create(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        if ($request->isGet()) {
            return new JsonResponse(['message' => 'Address form']);
        }

        $dto = AddressDTO::fromArray($request->getBody());

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $id = $customerService->addAddress((int) $customerId, $dto);
            return JsonResponse::success(['address_id' => $id], 'Address created', 201);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        }
    }

    public function edit(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        $addressId = (int) $request->getQueryParam('id', 0);
        if ($addressId <= 0) {
            return JsonResponse::error('Invalid address ID', 400);
        }

        if ($request->isGet()) {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $addresses = $customerService->getAddresses((int) $customerId);
            foreach ($addresses as $addr) {
                if ($addr->id === $addressId) {
                    return JsonResponse::success($addr->toArray());
                }
            }
            return JsonResponse::error('Address not found', 404);
        }

        $dto = AddressDTO::fromArray($request->getBody());

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->updateAddress((int) $customerId, $addressId, $dto);
            return JsonResponse::success(null, 'Address updated');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }

    public function delete(Request $request): Response
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        if (!$customerId) {
            return JsonResponse::error('Not logged in', 401);
        }

        $addressId = (int) $request->getInput('address_id', $request->getQueryParam('id', 0));
        if ($addressId <= 0) {
            return JsonResponse::error('Invalid address ID', 400);
        }

        try {
            $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
            $customerService->deleteAddress((int) $customerId, $addressId);
            return JsonResponse::success(null, 'Address deleted');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }
}
