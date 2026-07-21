<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\AdminUserRepository;
use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\CategoryRepository;

class DashboardService
{
    private AdminUserRepository $adminUserRepo;
    private OrderRepository $orderRepo;
    private CustomerRepository $customerRepo;
    private ProductRepository $productRepo;
    private CategoryRepository $categoryRepo;

    public function __construct(
        AdminUserRepository $adminUserRepo,
        OrderRepository $orderRepo,
        CustomerRepository $customerRepo,
        ProductRepository $productRepo,
        CategoryRepository $categoryRepo
    ) {
        $this->adminUserRepo = $adminUserRepo;
        $this->orderRepo = $orderRepo;
        $this->customerRepo = $customerRepo;
        $this->productRepo = $productRepo;
        $this->categoryRepo = $categoryRepo;
    }

    public function getStats(): array
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        return [
            'total_orders'       => $this->orderRepo->count(),
            'total_customers'    => $this->customerRepo->count(),
            'total_products'     => $this->productRepo->count(),
            'total_categories'   => $this->categoryRepo->count(),
            'total_admins'       => $this->adminUserRepo->count(),
            'revenue_today'      => $this->orderRepo->getRevenue($today, $today . ' 23:59:59'),
            'revenue_month'      => $this->orderRepo->getRevenue($monthStart),
            'revenue_total'      => $this->orderRepo->getRevenue(),
            'orders_today'       => $this->orderRepo->count(),
            'pending_orders'     => $this->orderRepo->count(0),
            'processing_orders'  => $this->orderRepo->count(1),
            'completed_orders'   => $this->orderRepo->count(3),
        ];
    }
}
