<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;

class CustomerController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $customerRepo = $this->container->get(\CoreCart\System\Repository\CustomerRepository::class);

        $offset = ($page - 1) * 20;
        $customers = $customerRepo->findAll(20, $offset);
        $total = $customerService->getCustomerCount();

        return JsonResponse::success([
            'customers' => array_map(fn($c) => $c->toArray(), $customers),
            'total'     => $total,
            'page'      => $page,
        ]);
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return JsonResponse::error('Invalid customer ID', 400);
        }

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $customer = $customerService->getCustomer($id);

        if (!$customer) {
            return JsonResponse::error('Customer not found', 404);
        }

        $addresses = $customerService->getAddresses($id);

        return JsonResponse::success([
            'customer'  => $customer->toArray(),
            'addresses' => array_map(fn($a) => $a->toArray(), $addresses),
        ]);
    }
}
