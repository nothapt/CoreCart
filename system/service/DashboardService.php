<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\AdminUserRepository;
use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\CategoryRepository;
use CoreCart\System\Infrastructure\OrderStatus;

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
        $todayStart = $today . ' 00:00:00';
        $todayEnd = $today . ' 23:59:59';

        return [
            'total_orders'       => $this->orderRepo->count(),
            'total_customers'    => $this->customerRepo->count(),
            'total_products'     => $this->productRepo->count(),
            'total_categories'   => $this->categoryRepo->count(),
            'total_admins'       => $this->adminUserRepo->count(),
            'revenue_today'      => $this->orderRepo->getRevenue($todayStart, $todayEnd),
            'revenue_month'      => $this->orderRepo->getRevenue($monthStart),
            'revenue_total'      => $this->orderRepo->getRevenue(),
            'orders_today'       => $this->orderRepo->countByDate($todayStart, $todayEnd),
            'pending_orders'     => $this->orderRepo->count(OrderStatus::Pending),
            'processing_orders'  => $this->orderRepo->count(OrderStatus::Processing),
            'completed_orders'   => $this->orderRepo->count(OrderStatus::Delivered),
        ];
    }
}