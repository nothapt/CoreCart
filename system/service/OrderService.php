<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Repository\AddressRepository;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\Infrastructure\OrderStatus;

class OrderService
{
    private OrderRepository $orderRepo;
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;
    private CustomerRepository $customerRepo;
    private AddressRepository $addressRepo;

    public function __construct(
        OrderRepository $orderRepo,
        CartRepository $cartRepo,
        ProductRepository $productRepo,
        CustomerRepository $customerRepo,
        AddressRepository $addressRepo
    ) {
        $this->orderRepo = $orderRepo;
        $this->cartRepo = $cartRepo;
        $this->productRepo = $productRepo;
        $this->customerRepo = $customerRepo;
        $this->addressRepo = $addressRepo;
    }

    public function getOrder(int $id): ?Order
    {
        return $this->orderRepo->findById($id);
    }

    public function getOrders(int $page = 1, int $perPage = 20, ?OrderStatus $status = null): array
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

    /**
     * Create order from session cart — atomic transaction.
     */
    public function createOrder(string $sessionId, OrderCreateDTO $dto): int
    {
        return $this->orderRepo->db()->transaction(function ($db) use ($sessionId, $dto) {
            // Lock cart rows for this session
            $cartItems = $db->query(
                "SELECT c.cart_id, c.product_id, c.quantity,
                        pd.name, p.price, p.quantity AS stock, p.status
                 FROM cc_cart c
                 LEFT JOIN cc_product p ON (c.product_id = p.product_id)
                 LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
                 WHERE c.session_id = :sid AND c.customer_id IS NULL
                 FOR UPDATE",
                ['sid' => $sessionId]
            );

            if (empty($cartItems)) {
                throw new \RuntimeException('Cart is empty');
            }

            $total = '0.0000';
            $orderItems = [];

            foreach ($cartItems as $item) {
                if ((int) $item['status'] !== 1) {
                    throw new \RuntimeException("Product '{$item['name']}' is not available");
                }
                if ((int) $item['stock'] < (int) $item['quantity']) {
                    throw new \RuntimeException(
                        "Insufficient stock for '{$item['name']}': requested {$item['quantity']}, available {$item['stock']}"
                    );
                }

                $subtotal = (string) bcmul($item['price'], (string) $item['quantity'], 4);
                $total = (string) bcadd($total, $subtotal, 4);

                $orderItems[] = [
                    'product_id' => (int) $item['product_id'],
                    'name'       => $item['name'] ?? '',
                    'quantity'   => (int) $item['quantity'],
                    'price'      => $item['price'],
                ];
            }

            // Build order data with snapshot
            $orderData = [
                'customer_id'       => $dto->customerId,
                'status'            => OrderStatus::Pending->value,
                'total'             => $total,
                'comment'           => $dto->comment,
                'customer_email'    => $dto->customerEmail ?? null,
                'customer_phone'    => $dto->customerPhone ?? null,
                'shipping_firstname'  => $dto->shippingFirstname ?? null,
                'shipping_lastname'   => $dto->shippingLastname ?? null,
                'shipping_address_1'  => $dto->shippingAddress1 ?? null,
                'shipping_address_2'  => $dto->shippingAddress2 ?? null,
                'shipping_city'       => $dto->shippingCity ?? null,
                'shipping_postcode'   => $dto->shippingPostcode ?? null,
                'shipping_country'    => $dto->shippingCountry ?? null,
                'shipping_zone'       => $dto->shippingZone ?? null,
                'currency_code'       => 'USD',
                'currency_value'      => '1.0000',
            ];

            // Create order
            $db->execute(
                "INSERT INTO cc_order (customer_id, status, total, comment,
                     customer_email, customer_phone,
                     shipping_firstname, shipping_lastname,
                     shipping_address_1, shipping_address_2,
                     shipping_city, shipping_postcode,
                     shipping_country, shipping_zone,
                     currency_code, currency_value)
                 VALUES (:cid, :status, :total, :comment,
                     :email, :phone,
                     :s_fn, :s_ln, :s_a1, :s_a2,
                     :s_city, :s_pc, :s_country, :s_zone,
                     :currency, :currency_val)",
                $orderData
            );

            $orderId = (int) $db->lastInsertId();

            // Create order items
            foreach ($orderItems as $item) {
                $db->execute(
                    "INSERT INTO cc_order_product (order_id, product_id, name, quantity, price)
                     VALUES (:oid, :pid, :name, :qty, :price)",
                    [
                        'oid'   => $orderId,
                        'pid'   => $item['product_id'],
                        'name'  => $item['name'],
                        'qty'   => $item['quantity'],
                        'price' => $item['price'],
                    ]
                );
            }

            // Atomically decrease stock and verify affected rows
            foreach ($orderItems as $item) {
                $affected = $db->execute(
                    "UPDATE cc_product SET quantity = quantity - :qty
                     WHERE product_id = :pid AND quantity >= :qty2",
                    ['qty' => $item['quantity'], 'qty2' => $item['quantity'], 'pid' => $item['product_id']]
                );
                if ($affected === 0) {
                    throw new \RuntimeException(
                        "Failed to decrease stock for product #{$item['product_id']}: concurrent modification"
                    );
                }
            }

            // Clear cart
            $db->execute(
                "DELETE FROM cc_cart WHERE session_id = :sid AND customer_id IS NULL",
                ['sid' => $sessionId]
            );

            return $orderId;
        });
    }

    /**
     * Create order from customer cart — atomic transaction.
     */
    public function createOrderFromCart(int $customerId, OrderCreateDTO $dto): int
    {
        return $this->orderRepo->db()->transaction(function ($db) use ($customerId, $dto) {
            $cartItems = $db->query(
                "SELECT c.cart_id, c.product_id, c.quantity,
                        pd.name, p.price, p.quantity AS stock, p.status
                 FROM cc_cart c
                 LEFT JOIN cc_product p ON (c.product_id = p.product_id)
                 LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
                 WHERE c.customer_id = :cid
                 FOR UPDATE",
                ['cid' => $customerId]
            );

            if (empty($cartItems)) {
                throw new \RuntimeException('Cart is empty');
            }

            $total = '0.0000';
            $orderItems = [];

            foreach ($cartItems as $item) {
                if ((int) $item['status'] !== 1) {
                    throw new \RuntimeException("Product '{$item['name']}' is not available");
                }
                if ((int) $item['stock'] < (int) $item['quantity']) {
                    throw new \RuntimeException(
                        "Insufficient stock for '{$item['name']}': requested {$item['quantity']}, available {$item['stock']}"
                    );
                }

                $subtotal = (string) bcmul($item['price'], (string) $item['quantity'], 4);
                $total = (string) bcadd($total, $subtotal, 4);

                $orderItems[] = [
                    'product_id' => (int) $item['product_id'],
                    'name'       => $item['name'] ?? '',
                    'quantity'   => (int) $item['quantity'],
                    'price'      => $item['price'],
                ];
            }

            $orderData = [
                'customer_id'       => $customerId,
                'status'            => OrderStatus::Pending->value,
                'total'             => $total,
                'comment'           => $dto->comment,
                'customer_email'    => $dto->customerEmail ?? null,
                'customer_phone'    => $dto->customerPhone ?? null,
                'shipping_firstname'  => $dto->shippingFirstname ?? null,
                'shipping_lastname'   => $dto->shippingLastname ?? null,
                'shipping_address_1'  => $dto->shippingAddress1 ?? null,
                'shipping_address_2'  => $dto->shippingAddress2 ?? null,
                'shipping_city'       => $dto->shippingCity ?? null,
                'shipping_postcode'   => $dto->shippingPostcode ?? null,
                'shipping_country'    => $dto->shippingCountry ?? null,
                'shipping_zone'       => $dto->shippingZone ?? null,
                'currency_code'       => 'USD',
                'currency_value'      => '1.0000',
            ];

            $db->execute(
                "INSERT INTO cc_order (customer_id, status, total, comment,
                     customer_email, customer_phone,
                     shipping_firstname, shipping_lastname,
                     shipping_address_1, shipping_address_2,
                     shipping_city, shipping_postcode,
                     shipping_country, shipping_zone,
                     currency_code, currency_value)
                 VALUES (:cid, :status, :total, :comment,
                     :email, :phone,
                     :s_fn, :s_ln, :s_a1, :s_a2,
                     :s_city, :s_pc, :s_country, :s_zone,
                     :currency, :currency_val)",
                $orderData
            );

            $orderId = (int) $db->lastInsertId();

            foreach ($orderItems as $item) {
                $db->execute(
                    "INSERT INTO cc_order_product (order_id, product_id, name, quantity, price)
                     VALUES (:oid, :pid, :name, :qty, :price)",
                    [
                        'oid'   => $orderId,
                        'pid'   => $item['product_id'],
                        'name'  => $item['name'],
                        'qty'   => $item['quantity'],
                        'price' => $item['price'],
                    ]
                );
            }

            foreach ($orderItems as $item) {
                $affected = $db->execute(
                    "UPDATE cc_product SET quantity = quantity - :qty
                     WHERE product_id = :pid AND quantity >= :qty2",
                    ['qty' => $item['quantity'], 'qty2' => $item['quantity'], 'pid' => $item['product_id']]
                );
                if ($affected === 0) {
                    throw new \RuntimeException(
                        "Failed to decrease stock for product #{$item['product_id']}: concurrent modification"
                    );
                }
            }

            $db->execute(
                "DELETE FROM cc_cart WHERE customer_id = :cid",
                ['cid' => $customerId]
            );

            return $orderId;
        });
    }

    public function updateStatus(int $id, OrderStatus $status): bool
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            throw new \RuntimeException("Order #{$id} not found");
        }

        $currentStatus = OrderStatus::fromInt($order->status);

        if (!$currentStatus->canTransitionTo($status)) {
            throw new \RuntimeException(
                "Cannot transition from '{$currentStatus->label()}' to '{$status->label()}'"
            );
        }

        return $this->orderRepo->updateStatus($id, $status);
    }

    public function cancelOrder(int $id, ?int $customerId = null): bool
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            throw new \RuntimeException("Order #{$id} not found");
        }

        if ($customerId !== null && $order->customerId !== $customerId) {
            throw new \RuntimeException('Order does not belong to this customer');
        }

        $currentStatus = OrderStatus::fromInt($order->status);
        if (!$currentStatus->canTransitionTo(OrderStatus::Cancelled)) {
            throw new \RuntimeException('Order cannot be cancelled at this stage');
        }

        // Atomically restore stock
        return $this->orderRepo->db()->transaction(function ($db) use ($order) {
            foreach ($order->items as $item) {
                $db->execute(
                    "UPDATE cc_product SET quantity = quantity + :qty WHERE product_id = :pid",
                    ['qty' => $item->quantity, 'pid' => $item->productId]
                );
            }

            $db->execute(
                "UPDATE cc_order SET status = :status WHERE order_id = :id",
                ['status' => OrderStatus::Cancelled->value, 'id' => $order->id]
            );

            return true;
        });
    }

    public function getRevenue(string $dateFrom = '', string $dateTo = ''): string
    {
        return $this->orderRepo->getRevenue($dateFrom, $dateTo);
    }
}
