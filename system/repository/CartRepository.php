<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Infrastructure\InsufficientStockException;

class CartRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findBySession(string $sessionId): array
    {
        $result = $this->db->query(
            "SELECT c.cart_id, c.customer_id, c.session_id, c.product_id, c.quantity, c.date_added,
                    pd.name, p.price, p.image, p.quantity AS p_quantity
             FROM cc_cart c
             LEFT JOIN cc_product p ON (c.product_id = p.product_id)
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             WHERE c.session_id = :sid AND c.customer_id IS NULL
             ORDER BY c.date_added ASC",
            ['sid' => $sessionId]
        );

        return array_map(fn(array $row) => $row, $result);
    }

    public function findByCustomer(int $customerId): array
    {
        $result = $this->db->query(
            "SELECT c.cart_id, c.customer_id, c.session_id, c.product_id, c.quantity, c.date_added,
                    pd.name, p.price, p.image, p.quantity AS p_quantity
             FROM cc_cart c
             LEFT JOIN cc_product p ON (c.product_id = p.product_id)
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             WHERE c.customer_id = :cid
             ORDER BY c.date_added ASC",
            ['cid' => $customerId]
        );

        return array_map(fn(array $row) => $row, $result);
    }

    public function findItem(int $cartId): ?array
    {
        $result = $this->db->query(
            "SELECT c.cart_id, c.customer_id, c.session_id, c.product_id, c.quantity, c.date_added,
                    pd.name, p.price, p.image, p.quantity AS p_quantity
             FROM cc_cart c
             LEFT JOIN cc_product p ON (c.product_id = p.product_id)
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             WHERE c.cart_id = :id",
            ['id' => $cartId]
        );

        return !empty($result) ? $result[0] : null;
    }

    public function findItemByProductForSession(string $sessionId, int $productId): ?array
    {
        $result = $this->db->query(
            "SELECT c.cart_id, c.quantity FROM cc_cart c
             WHERE c.session_id = :sid AND c.product_id = :pid AND c.customer_id IS NULL",
            ['sid' => $sessionId, 'pid' => $productId]
        );

        return !empty($result) ? $result[0] : null;
    }

    public function findItemByProductForCustomer(int $customerId, int $productId): ?array
    {
        $result = $this->db->query(
            "SELECT c.cart_id, c.quantity FROM cc_cart c
             WHERE c.customer_id = :cid AND c.product_id = :pid",
            ['cid' => $customerId, 'pid' => $productId]
        );

        return !empty($result) ? $result[0] : null;
    }

    public function addItem(string $sessionId, int $productId, int $quantity): int
    {
        $existing = $this->findItemByProductForSession($sessionId, $productId);

        if ($existing) {
            $this->updateQuantity((int) $existing['cart_id'], (int) $existing['quantity'] + $quantity);
            return (int) $existing['cart_id'];
        }

        $this->db->execute(
            "INSERT INTO cc_cart (session_id, product_id, quantity) VALUES (:sid, :pid, :qty)",
            ['sid' => $sessionId, 'pid' => $productId, 'qty' => $quantity]
        );

        return (int) $this->db->lastInsertId();
    }

    public function addItemForCustomer(int $customerId, int $productId, int $quantity): int
    {
        $existing = $this->findItemByProductForCustomer($customerId, $productId);

        if ($existing) {
            $this->updateQuantity((int) $existing['cart_id'], (int) $existing['quantity'] + $quantity);
            return (int) $existing['cart_id'];
        }

        $this->db->execute(
            "INSERT INTO cc_cart (customer_id, product_id, quantity) VALUES (:cid, :pid, :qty)",
            ['cid' => $customerId, 'pid' => $productId, 'qty' => $quantity]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateQuantity(int $cartId, int $quantity): bool
    {
        return $this->db->execute(
            "UPDATE cc_cart SET quantity = :qty WHERE cart_id = :id",
            ['qty' => $quantity, 'id' => $cartId]
        ) > 0;
    }

    public function removeItem(int $cartId): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_cart WHERE cart_id = :id",
            ['id' => $cartId]
        ) > 0;
    }

    public function clearSession(string $sessionId): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_cart WHERE session_id = :sid AND customer_id IS NULL",
            ['sid' => $sessionId]
        ) > 0;
    }

    public function clearCustomer(int $customerId): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_cart WHERE customer_id = :cid",
            ['cid' => $customerId]
        ) > 0;
    }

    public function mergeSessionToCustomer(string $sessionId, int $customerId): int
    {
        $items = $this->findBySession($sessionId);
        $merged = 0;

        foreach ($items as $item) {
            $existing = $this->findItemByProductForCustomer($customerId, $item['product_id']);

            if ($existing) {
                $this->updateQuantity((int) $existing['cart_id'], (int) $existing['quantity'] + (int) $item['quantity']);
                $this->removeItem((int) $item['cart_id']);
            } else {
                $this->db->execute(
                    "UPDATE cc_cart SET customer_id = :cid, session_id = NULL WHERE cart_id = :id",
                    ['cid' => $customerId, 'id' => $item['cart_id']]
                );
            }
            $merged++;
        }

        return $merged;
    }

    public function count(string $sessionId): int
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS total FROM cc_cart WHERE session_id = :sid AND customer_id IS NULL",
            ['sid' => $sessionId]
        );
        return (int) ($result[0]['total'] ?? 0);
    }

    public function countByCustomer(int $customerId): int
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(quantity), 0) AS total FROM cc_cart WHERE customer_id = :cid",
            ['cid' => $customerId]
        );
        return (int) ($result[0]['total'] ?? 0);
    }

    public function getTotal(string $sessionId): string
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(c.quantity * p.price), 0) AS total
             FROM cc_cart c
             LEFT JOIN cc_product p ON (c.product_id = p.product_id)
             WHERE c.session_id = :sid AND c.customer_id IS NULL",
            ['sid' => $sessionId]
        );
        return $result[0]['total'] ?? '0.0000';
    }

    public function getTotalByCustomer(int $customerId): string
    {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(c.quantity * p.price), 0) AS total
             FROM cc_cart c
             LEFT JOIN cc_product p ON (c.product_id = p.product_id)
             WHERE c.customer_id = :cid",
            ['cid' => $customerId]
        );
        return $result[0]['total'] ?? '0.0000';
    }
}
