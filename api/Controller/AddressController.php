<?php
declare(strict_types=1);

namespace CoreCart\Api\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Service\CustomerService;

final class AddressController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $customerId = $request->getUserId();
        if ($customerId === null) {
            return JsonResponse::error('Authentication required', 401);
        }

        $customerService = $this->container->get(CustomerService::class);
        $addresses = $customerService->getAddresses($customerId);

        return JsonResponse::success($addresses);
    }

    public function create(Request $request): Response
    {
        $customerId = $request->getUserId();
        if ($customerId === null) {
            return JsonResponse::error('Authentication required', 401);
        }

        $body = $request->getBody();
        $dto = \CoreCart\System\Dto\AddressDTO::fromArray($body);

        $customerService = $this->container->get(CustomerService::class);

        try {
            $addressId = $customerService->addAddress($customerId, $dto);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422);
        }

        return JsonResponse::success(['address_id' => $addressId], 'Address created', 201);
    }
}
