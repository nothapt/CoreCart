<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\Order;
use CoreCart\System\Entity\OrderItem;
use CoreCart\System\Infrastructure\OrderStatus;

class OrderRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function db(): Database
    {
        return $this->db;
    }

    public function findById(int $id): ?Order
    {
        $result = $this->db->query(
            "SELECT o.order_id, o.customer_id, o.status, o.total, o.comment,
                    o.customer_email, o.customer_phone,
                    o.shipping_firstname, o.shipping_lastname,
                    o.shipping_address_1, o.shipping_address_2,
                    o.shipping_city, o.shipping_postcode,
                    o.shipping_country, o.shipping_zone,
                    o.currency_code, o.currency_value,
                    o.date_added, o.date_modified
             FROM cc_order o
             WHERE o.order_id = :id",
            ['id' => $id]
        );

        if (empty($result)) {
            return null;
        }

        $order = Order::fromRow($result[0]);
        $items = $this->findItems($id);

        return new Order(
            id: $order->id,
            customerId: $order->customerId,
            status: $order->status,
            total: $order->total,
            comment: $order->comment,
            customerEmail: $order->customerEmail,
            customerPhone: $order->customerPhone,
            shippingFirstname: $order->shippingFirstname,
            shippingLastname: $order->shippingLastname,
            shippingAddress1: $order->shippingAddress1,
            shippingAddress2: $order->shippingAddress2,
            shippingCity: $order->shippingCity,
            shippingPostcode: $order->shippingPostcode,
            shippingCountry: $order->shippingCountry,
            shippingZone: $order->shippingZone,
            currencyCode: $order->currencyCode,
            currencyValue: $order->currencyValue,
            dateAdded: $order->dateAdded,
            dateModified: $order->dateModified,
            items: $items,
        );
    }

    public function findItems(int $orderId): array
    {
        $result = $this->db->query(
            "SELECT order_product_id, order_id, product_id, name, quantity, price
             FROM cc_order_product
             WHERE order_id = :id",
            ['id' => $orderId]
        );

        return array_map(OrderItem::fromRow(...), $result);
    }

    public function findAll(int $limit = 20, int $offset = 0, ?OrderStatus $status = null): array
    {
        $sql = "SELECT o.order_id, o.customer_id, o.status, o.total, o.comment,
                       o.customer_email, o.shipping_firstname, o.shipping_lastname,
                       o.date_added, o.date_modified
                FROM cc_order o";

        $params = [];

        if ($status !== null) {
            $sql .= " WHERE o.status = :status";
            $params['status'] = $status->value;
        }

        $sql .= " ORDER BY o.date_added DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        $result = $this->db->query($sql, $params);

        return array_map(Order::fromRow(...), $result);
    }

    public function findByCustomer(int $customerId, int $limit = 20, int $offset = 0): array
    {
        $result = $this->db->query(
            "SELECT order_id, customer_id, status, total, comment, date_added, date_modified
             FROM cc_order
             WHERE customer_id = :cid
             ORDER BY date_added DESC
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset,
            ['cid' => $customerId]
        );

        return array_map(Order::fromRow(...), $result);
    }

    public function belongsToCustomer(int $orderId, int $customerId): bool
    {
        $result = $this->db->query(
            "SELECT order_id FROM cc_order WHERE order_id = :id AND customer_id = :cid",
            ['id' => $orderId, 'cid' => $customerId]
        );
        return !empty($result);
    }

    public function create(array $orderData, array $items): int
    {
        return $this->db->transaction(function ($db) use ($orderData, $items) {
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
                     :s_fn, :s_ln,
                     :s_a1, :s_a2,
                     :s_city, :s_pc,
                     :s_country, :s_zone,
                     :currency, :currency_val)",
                [
                    'cid'          => $orderData['customer_id'] ?? null,
                    'status'       => $orderData['status'] ?? OrderStatus::Pending->value,
                    'total'        => $orderData['total'] ?? '0.0000',
                    'comment'      => $orderData['comment'] ?? null,
                    'email'        => $orderData['customer_email'] ?? null,
                    'phone'        => $orderData['customer_phone'] ?? null,
                    's_fn'         => $orderData['shipping_firstname'] ?? null,
                    's_ln'         => $orderData['shipping_lastname'] ?? null,
                    's_a1'         => $orderData['shipping_address_1'] ?? null,
                    's_a2'         => $orderData['shipping_address_2'] ?? null,
                    's_city'       => $orderData['shipping_city'] ?? null,
                    's_pc'         => $orderData['shipping_postcode'] ?? null,
                    's_country'    => $orderData['shipping_country'] ?? null,
                    's_zone'       => $orderData['shipping_zone'] ?? null,
                    'currency'     => $orderData['currency_code'] ?? 'USD',
                    'currency_val' => $orderData['currency_value'] ?? '1.0000',
                ]
            );

            $orderId = (int) $db->lastInsertId();

            foreach ($items as $item) {
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

            return $orderId;
        });
    }

    public function updateStatus(int $id, OrderStatus $status, string $comment = ''): bool
    {
        if ($comment !== '') {
            return $this->db->execute(
                "UPDATE cc_order SET status = :status, comment = :comment WHERE order_id = :id",
                ['status' => $status->value, 'comment' => $comment, 'id' => $id]
            ) > 0;
        }

        return $this->db->execute(
            "UPDATE cc_order SET status = :status WHERE order_id = :id",
            ['status' => $status->value, 'id' => $id]
        ) > 0;
    }

    public function updateComment(int $id, string $comment): bool
    {
        return $this->db->execute(
            "UPDATE cc_order SET comment = :comment WHERE order_id = :id",
            ['comment' => $comment, 'id' => $id]
        ) > 0;
    }

    public function findByIdForUpdate(int $id): ?Order
    {
        $result = $this->db->query(
            "SELECT o.order_id, o.customer_id, o.status, o.total, o.comment,
                    o.customer_email, o.customer_phone,
                    o.shipping_firstname, o.shipping_lastname,
                    o.shipping_address_1, o.shipping_address_2,
                    o.shipping_city, o.shipping_postcode,
                    o.shipping_country, o.shipping_zone,
                    o.currency_code, o.currency_value,
                    o.date_added, o.date_modified
             FROM cc_order o
             WHERE o.order_id = :id
             FOR UPDATE",
            ['id' => $id]
        );

        if (empty($result)) {
            return null;
        }

        $order = Order::fromRow($result[0]);
        $items = $this->findItems($id);

        return new Order(
            id: $order->id,
            customerId: $order->customerId,
            status: $order->status,
            total: $order->total,
            comment: $order->comment,
            customerEmail: $order->customerEmail,
            customerPhone: $order->customerPhone,
            shippingFirstname: $order->shippingFirstname,
            shippingLastname: $order->shippingLastname,
            shippingAddress1: $order->shippingAddress1,
            shippingAddress2: $order->shippingAddress2,
            shippingCity: $order->shippingCity,
            shippingPostcode: $order->shippingPostcode,
            shippingCountry: $order->shippingCountry,
            shippingZone: $order->shippingZone,
            currencyCode: $order->currencyCode,
            currencyValue: $order->currencyValue,
            dateAdded: $order->dateAdded,
            dateModified: $order->dateModified,
            items: $items,
        );
    }

    public function addHistory(int $orderId, OrderStatus $status, string $comment, bool $notifyCustomer = false, ?int $adminUserId = null): void
    {
        $this->db->execute(
            "INSERT INTO cc_order_history (order_id, status, comment, notify_customer, admin_user_id)
             VALUES (:oid, :status, :comment, :notify, :admin)",
            [
                'oid'    => $orderId,
                'status' => $status->value,
                'comment' => $comment,
                'notify' => $notifyCustomer ? 1 : 0,
                'admin'  => $adminUserId,
            ]
        );
    }

    public function getHistory(int $orderId): array
    {
        return $this->db->query(
            "SELECT order_history_id, order_id, status, comment, notify_customer,
                    admin_user_id, date_added
             FROM cc_order_history
             WHERE order_id = :oid
             ORDER BY date_added DESC",
            ['oid' => $orderId]
        );
    }

    public function count(?OrderStatus $status = null): int
    {
        $sql = "SELECT COUNT(*) AS total FROM cc_order";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status->value;
        }

        $result = $this->db->query($sql, $params);
        return (int) ($result[0]['total'] ?? 0);
    }

    public function countByDate(string $dateFrom, string $dateTo): int
    {
        $sql = "SELECT COUNT(*) AS total FROM cc_order WHERE date_added >= :from AND date_added <= :to";
        $result = $this->db->query($sql, ['from' => $dateFrom, 'to' => $dateTo]);
        return (int) ($result[0]['total'] ?? 0);
    }

    public function getRevenue(string $dateFrom = '', string $dateTo = ''): string
    {
        $sql = "SELECT COALESCE(SUM(total), 0) AS revenue FROM cc_order WHERE status IN (:p, :s, :d)";
        $params = [
            'p' => OrderStatus::Processing->value,
            's' => OrderStatus::Shipped->value,
            'd' => OrderStatus::Delivered->value,
        ];

        if ($dateFrom !== '') {
            $sql .= " AND date_added >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= " AND date_added <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $result = $this->db->query($sql, $params);
        return $result[0]['revenue'] ?? '0.0000';
    }
}
