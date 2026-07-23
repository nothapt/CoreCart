<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\Infrastructure\OrderStatus;

class OrderService
{
    private OrderRepository $orderRepo;

    public function __construct(
        OrderRepository $orderRepo,
    ) {
        $this->orderRepo = $orderRepo;
    }

    public function getOrder(int $id): ?\CoreCart\System\Entity\Order
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

            $params = [
                'cid'          => $dto->customerId,
                'status'       => OrderStatus::Pending->value,
                'total'        => $total,
                'comment'      => $dto->comment,
                'email'        => $dto->customerEmail ?? null,
                'phone'        => $dto->customerPhone ?? null,
                's_fn'         => $dto->shippingFirstname ?? null,
                's_ln'         => $dto->shippingLastname ?? null,
                's_a1'         => $dto->shippingAddress1 ?? null,
                's_a2'         => $dto->shippingAddress2 ?? null,
                's_city'       => $dto->shippingCity ?? null,
                's_pc'         => $dto->shippingPostcode ?? null,
                's_country'    => $dto->shippingCountry ?? null,
                's_zone'       => $dto->shippingZone ?? null,
                'currency'     => 'USD',
                'currency_val' => '1.0000',
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
                $params
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

$params = [
                'cid'          => $customerId,
                'status'       => OrderStatus::Pending->value,
                'total'        => $total,
                'comment'      => $dto->comment,
                'email'        => $dto->customerEmail ?? null,
                'phone'        => $dto->customerPhone ?? null,
                's_fn'         => $dto->shippingFirstname ?? null,
                's_ln'         => $dto->shippingLastname ?? null,
                's_a1'         => $dto->shippingAddress1 ?? null,
                's_a2'         => $dto->shippingAddress2 ?? null,
                's_city'       => $dto->shippingCity ?? null,
                's_pc'         => $dto->shippingPostcode ?? null,
                's_country'    => $dto->shippingCountry ?? null,
                's_zone'       => $dto->shippingZone ?? null,
                'currency'     => 'USD',
                'currency_val' => '1.0000',
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
                $params
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

    /**
     * Atomic order status transition with row lock, history, and stock return on Cancelled.
     */
    public function transitionStatus(int $id, OrderStatus $newStatus, string $comment = '', ?int $adminUserId = null): bool
    {
        return $this->orderRepo->db()->transaction(function ($db) use ($id, $newStatus, $comment, $adminUserId) {
            // SELECT ... FOR UPDATE — locks the order row
            $order = $this->orderRepo->findByIdForUpdate($id);
            if ($order === null) {
                throw new \RuntimeException("Order #{$id} not found");
            }

            $currentStatus = OrderStatus::tryFrom($order->status);
            if ($currentStatus === null) {
                throw new \RuntimeException("Order has invalid status: {$order->status}");
            }

            if (!$currentStatus->canTransitionTo($newStatus)) {
                throw new \RuntimeException(
                    "Cannot transition from '{$currentStatus->label()}' to '{$newStatus->label()}'"
                );
            }

            // Update status
            $this->orderRepo->updateStatus($id, $newStatus, $comment);

            // Record history entry
            $this->orderRepo->addHistory($id, $newStatus, $comment, false, $adminUserId);

            // Return stock when cancelling
            if ($newStatus === OrderStatus::Cancelled) {
                foreach ($order->items as $item) {
                    $db->execute(
                        "UPDATE cc_product SET quantity = quantity + :qty WHERE product_id = :pid",
                        ['qty' => $item->quantity, 'pid' => $item->productId]
                    );
                }
            }

            return true;
        });
    }

    /**
     * @deprecated Use transitionStatus() instead
     */
    public function updateStatus(int $id, OrderStatus $status, string $comment = ''): bool
    {
        return $this->transitionStatus($id, $status, $comment);
    }

    /**
     * @deprecated Use transitionStatus() with OrderStatus::Cancelled instead
     */
    public function cancelOrder(int $id, ?int $customerId = null): bool
    {
        $order = $this->orderRepo->findById($id);
        if (!$order) {
            throw new \RuntimeException("Order #{$id} not found");
        }

        if ($customerId !== null && $order->customerId !== $customerId) {
            throw new \RuntimeException('Order does not belong to this customer');
        }

        return $this->transitionStatus($id, OrderStatus::Cancelled, 'Cancelled by customer');
    }

    public function getRevenue(string $dateFrom = '', string $dateTo = ''): string
    {
        return $this->orderRepo->getRevenue($dateFrom, $dateTo);
    }

    public function getHistory(int $orderId): array
    {
        return $this->orderRepo->getHistory($orderId);
    }
}