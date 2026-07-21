<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\Order;
use CoreCart\System\Entity\OrderItem;

class OrderRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Order
    {
        $result = $this->db->query(
            "SELECT o.order_id, o.customer_id, o.status, o.total, o.comment, o.date_added, o.date_modified,
                    CONCAT(c.firstname, ' ', c.lastname) AS customer_name
             FROM cc_order o
             LEFT JOIN cc_address c ON (o.customer_id = c.customer_id AND c.`default` = 1)
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
            dateAdded: $order->dateAdded,
            dateModified: $order->dateModified,
            customerName: $order->customerName,
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

    public function findAll(int $limit = 20, int $offset = 0, ?int $status = null): array
    {
        $sql = "SELECT o.order_id, o.customer_id, o.status, o.total, o.comment, o.date_added, o.date_modified
                FROM cc_order o";

        $params = [];

        if ($status !== null) {
            $sql .= " WHERE o.status = :status";
            $params['status'] = $status;
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

    public function create(array $orderData, array $items): int
    {
        return $this->db->transaction(function ($db) use ($orderData, $items) {
            $db->execute(
                "INSERT INTO cc_order (customer_id, status, total, comment)
                 VALUES (:cid, :status, :total, :comment)",
                [
                    'cid'     => $orderData['customer_id'] ?? null,
                    'status'  => $orderData['status'] ?? 0,
                    'total'   => $orderData['total'] ?? '0.0000',
                    'comment' => $orderData['comment'] ?? null,
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

    public function updateStatus(int $id, int $status): bool
    {
        return $this->db->execute(
            "UPDATE cc_order SET status = :status WHERE order_id = :id",
            ['status' => $status, 'id' => $id]
        ) > 0;
    }

    public function updateComment(int $id, string $comment): bool
    {
        return $this->db->execute(
            "UPDATE cc_order SET comment = :comment WHERE order_id = :id",
            ['comment' => $comment, 'id' => $id]
        ) > 0;
    }

    public function count(?int $status = null): int
    {
        $sql = "SELECT COUNT(*) AS total FROM cc_order";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status;
        }

        $result = $this->db->query($sql, $params);
        return (int) ($result[0]['total'] ?? 0);
    }

    public function getRevenue(string $dateFrom = '', string $dateTo = ''): string
    {
        $sql = "SELECT COALESCE(SUM(total), 0) AS revenue FROM cc_order WHERE status >= 1";
        $params = [];

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
