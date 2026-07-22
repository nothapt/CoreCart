<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\Product;

class ProductRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id, int $languageId = 1): ?Product
    {
        $result = $this->db->query(
            "SELECT p.product_id, p.model, p.sku, p.price, p.quantity, p.image, p.status, p.date_added,
                    pd.name, pd.description
             FROM cc_product p
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = :lang)
             WHERE p.product_id = :id",
            ['id' => $id, 'lang' => $languageId]
        );

        return !empty($result) ? Product::fromRow($result[0]) : null;
    }

    public function findAll(int $limit = 20, int $offset = 0, string $sort = 'date_added', string $order = 'DESC'): array
    {
        $sortColumn = match ($sort) {
            'name' => 'pd.name',
            'price' => 'p.price',
            'quantity' => 'p.quantity',
            default => 'p.date_added',
        };
        $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $result = $this->db->query(
            "SELECT p.product_id, p.model, p.sku, p.price, p.quantity, p.image, p.status, p.date_added,
                    pd.name
             FROM cc_product p
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             ORDER BY {$sortColumn} {$orderDir}
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );

        return array_map(Product::fromRow(...), $result);
    }

    public function findActive(int $limit = 20, int $offset = 0): array
    {
        $result = $this->db->query(
            "SELECT p.product_id, p.model, p.sku, p.price, p.quantity, p.image, p.status, p.date_added,
                    pd.name
             FROM cc_product p
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             WHERE p.status = 1
             ORDER BY p.date_added DESC
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );

        return array_map(Product::fromRow(...), $result);
    }

    public function findByCategory(int $categoryId, int $limit = 20, int $offset = 0): array
    {
        $result = $this->db->query(
            "SELECT p.product_id, p.model, p.sku, p.price, p.quantity, p.image, p.status, p.date_added,
                    pd.name
             FROM cc_product p
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             INNER JOIN cc_product_to_category p2c ON (p.product_id = p2c.product_id)
             WHERE p.status = 1 AND p2c.category_id = :cat_id
             ORDER BY p.date_added DESC
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset,
            ['cat_id' => $categoryId]
        );

        return array_map(Product::fromRow(...), $result);
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $result = $this->db->query(
            "SELECT p.product_id, p.model, p.sku, p.price, p.quantity, p.image, p.status, p.date_added,
                    pd.name
             FROM cc_product p
             LEFT JOIN cc_product_description pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
             WHERE p.status = 1 AND pd.name LIKE :q
             ORDER BY pd.name ASC
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset,
            ['q' => '%' . $query . '%']
        );

        return array_map(Product::fromRow(...), $result);
    }

    public function count(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM cc_product");
        return (int) ($result[0]['total'] ?? 0);
    }

    public function countActive(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM cc_product WHERE status = 1");
        return (int) ($result[0]['total'] ?? 0);
    }

    public function countByCategory(int $categoryId): int
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS total FROM cc_product_to_category WHERE category_id = :cat_id",
            ['cat_id' => $categoryId]
        );
        return (int) ($result[0]['total'] ?? 0);
    }

    public function create(array $product, array $description): int
    {
        return $this->db->transaction(function ($db) use ($product, $description) {
            $db->execute(
                "INSERT INTO cc_product (model, sku, price, quantity, image, status)
                 VALUES (:model, :sku, :price, :quantity, :image, :status)",
                [
                    'model'    => $product['model'],
                    'sku'      => $product['sku'] ?? null,
                    'price'    => $product['price'] ?? 0,
                    'quantity' => $product['quantity'] ?? 0,
                    'image'    => $product['image'] ?? null,
                    'status'   => $product['status'] ?? 1,
                ]
            );

            $productId = (int) $db->lastInsertId();

            foreach ($description as $langId => $desc) {
                $db->execute(
                    "INSERT INTO cc_product_description (product_id, language_id, name, description)
                     VALUES (:pid, :lang, :name, :desc)",
                    [
                        'pid'  => $productId,
                        'lang' => $langId,
                        'name' => $desc['name'],
                        'desc' => $desc['description'] ?? '',
                    ]
                );
            }

            return $productId;
        });
    }

    public function update(int $id, array $product): bool
    {
        $result = $this->db->execute(
            "UPDATE cc_product
             SET model = :model, sku = :sku, price = :price, quantity = :quantity, image = :image, status = :status
             WHERE product_id = :id",
            [
                'id'       => $id,
                'model'    => $product['model'],
                'sku'      => $product['sku'] ?? null,
                'price'    => $product['price'] ?? 0,
                'quantity' => $product['quantity'] ?? 0,
                'image'    => $product['image'] ?? null,
                'status'   => $product['status'] ?? 1,
            ]
        );

        if (isset($product['name'], $product['description'])) {
            $this->db->execute(
                "UPDATE cc_product_description
                 SET name = :name, description = :desc
                 WHERE product_id = :pid AND language_id = 1",
                [
                    'pid'  => $id,
                    'name' => $product['name'],
                    'desc' => $product['description'],
                ]
            );
        }

        return $result > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_product WHERE product_id = :id",
            ['id' => $id]
        ) > 0;
    }

    public function updateQuantity(int $id, int $quantity): bool
    {
        return $this->db->execute(
            "UPDATE cc_product SET quantity = :qty WHERE product_id = :id",
            ['qty' => $quantity, 'id' => $id]
        ) > 0;
    }

    public function decreaseStock(int $id, int $quantity): bool
    {
        return $this->db->execute(
            "UPDATE cc_product SET quantity = quantity - :qty WHERE product_id = :id AND quantity >= :qty2",
            ['qty' => $quantity, 'qty2' => $quantity, 'id' => $id]
        ) > 0;
    }

    public function inStock(int $id): bool
    {
        $result = $this->db->query(
            "SELECT quantity FROM cc_product WHERE product_id = :id",
            ['id' => $id]
        );
        return !empty($result) && (int) $result[0]['quantity'] > 0;
    }
}
