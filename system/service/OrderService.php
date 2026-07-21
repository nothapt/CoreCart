<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Entity\Order;
use CoreCart\System\Dto\OrderCreateDTO;

class OrderService
{
    private OrderRepository $orderRepo;
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;
    private CustomerRepository $customerRepo;

    public function __construct(
        OrderRepository $orderRepo,
        CartRepository $cartRepo,
        ProductRepository $productRepo,
        CustomerRepository $customerRepo
    ) {
        $this->orderRepo = $orderRepo;
        $this->cartRepo = $cartRepo;
        $this->productRepo = $productRepo;
        $this->customerRepo = $customerRepo;
    }

    public function getOrder(int $id): ?Order
    {
        return $this->orderRepo->findById($id);
    }

    public function getOrders(int $page = 1, int $perPage = 20, ?int $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $orders = $this->orderRepo->findAll($perPage, $offset, $status);
        $total = $this->orderRepo->count($status);

        return [
            'orders'   => $orders,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
        ];
    }

    public function getCustomerOrders(int $customerId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $orders = $this->orderRepo->findByCustomer($customerId, $perPage, $offset);

        return [
            'orders' => $orders,
            'page'   => $page,
        ];
    }

    public function createOrder(string $sessionId, OrderCreateDTO $dto): int
    {
        $items = $this->cartRepo->findBySession($sessionId);

        if (empty($items)) {
            throw new \RuntimeException('Cart is empty');
        }

        // Validate stock and build order items
        $orderItems = [];
        $total = '0.0000';

        foreach ($items as $item) {
            $product = $this->productRepo->findById($item->productId);

            if (!$product) {
                throw new \RuntimeException("Product #{$item->productId} not found");
            }

            if ($product->status !== 1) {
                throw new \RuntimeException("Product '{$item->productName}' is not available");
            }

            if ($product->quantity < $item->quantity) {
                throw new \RuntimeException(
                    "Insufficient stock for '{$item->productName}'. Available: {$product->quantity}"
                );
            }

            $subtotal = (string) bcmul($product->price, (string) $item->quantity, 4);
            $total = (string) bcadd($total, $subtotal, 4);

            $orderItems[] = [
                'product_id' => $item->productId,
                'name'       => $item->productName ?? $product->name ?? '',
                'quantity'   => $item->quantity,
                'price'      => $product->price,
            ];
        }

        // Create order and decrease stock in transaction
        $orderId = $this->orderRepo->create(
            [
                'customer_id' => $dto->customerId,
                'status'      => 0,
                'total'       => $total,
                'comment'     => $dto->comment,
            ],
            $orderItems
        );

        // Decrease stock for each product
        foreach ($orderItems as $item) {
            $this->productRepo->decreaseStock($item['product_id'], $item['quantity']);
        }

        // Clear the cart
        $this->cartRepo->clearSession($sessionId);

        return $orderId;
    }

    public function createOrderFromCart(int $customerId, OrderCreateDTO $dto): int
    {
        $items = $this->cartRepo->findByCustomer($customerId);

        if (empty($items)) {
            throw new \RuntimeException('Cart is empty');
        }

        $orderItems = [];
        $total = '0.0000';

        foreach ($items as $item) {
            $product = $this->productRepo->findById($item->productId);

            if (!$product) {
                throw new \RuntimeException("Product #{$item->productId} not found");
            }

            if ($product->status !== 1) {
                throw new \RuntimeException("Product '{$item->productName}' is not available");
            }

            if ($product->quantity < $item->quantity) {
                throw new \RuntimeException(
                    "Insufficient stock for '{$item->productName}'. Available: {$product->quantity}"
                );
            }

            $subtotal = (string) bcmul($product->price, (string) $item->quantity, 4);
            $total = (string) bcadd($total, $subtotal, 4);

            $orderItems[] = [
                'product_id' => $item->productId,
                'name'       => $item->productName ?? $product->name ?? '',
                'quantity'   => $item->quantity,
                'price'      => $product->price,
            ];
        }

        $orderId = $this->orderRepo->create(
            [
                'customer_id' => $customerId,
                'status'      => 0,
                'total'       => $total,
                'comment'     => $dto->comment,
            ],
            $orderItems
        );

        foreach ($orderItems as $item) {
            $this->productRepo->decreaseStock($item['product_id'], $item['quantity']);
        }

        $this->cartRepo->clearCustomer($customerId);

        return $orderId;
    }

    public function updateStatus(int $id, int $status): bool
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            throw new \RuntimeException("Order #{$id} not found");
        }

        if ($status < 0 || $status > 9) {
            throw new \InvalidArgumentException('Invalid order status');
        }

        return $this->orderRepo->updateStatus($id, $status);
    }

    public function cancelOrder(int $id, ?int $customerId = null): bool
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            throw new \RuntimeException("Order #{$id} not found");
        }

        if ($customerId && $order->customerId !== $customerId) {
            throw new \RuntimeException('Order does not belong to this customer');
        }

        if ($order->status >= 2) {
            throw new \RuntimeException('Order cannot be cancelled at this stage');
        }

        // Restore stock
        foreach ($order->items as $item) {
            $product = $this->productRepo->findById($item->productId);
            if ($product) {
                $this->productRepo->updateQuantity($item->productId, $product->quantity + $item->quantity);
            }
        }

        return $this->orderRepo->updateStatus($id, 9);
    }

    public function getRevenue(string $dateFrom = '', string $dateTo = ''): string
    {
        return $this->orderRepo->getRevenue($dateFrom, $dateTo);
    }
}
