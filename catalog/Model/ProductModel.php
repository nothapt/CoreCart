<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Model;

use CoreCart\System\Engine\Model;

/**
 * Product Model
 *
 * Handles all database queries related to products.
 * Composite operations use transactions.
 */
class ProductModel extends Model
{
    /**
     * Get a single product by ID with its description.
     *
     * @return array|null Product data or null if not found
     */
    public function getProduct(int $productId, int $languageId = 1): ?array
    {
        $sql = "SELECT p.product_id, p.model, p.sku, p.price, p.quantity, p.image, p.status, p.date_added,
                       pd.name, pd.description
                FROM cc_product p
                LEFT JOIN cc_product_description pd
                    ON (p.product_id = pd.product_id AND pd.language_id = :language_id)
                WHERE p.product_id = :product_id AND p.status = 1";

        $result = $this->db->query($sql, [
            'product_id' => $productId,
            'language_id' => $languageId,
        ]);

        return $result[0] ?? null;
    }

    /**
     * Get a list of products for the storefront.
     *
     * @param array $data  Options: 'limit', 'offset', 'sort', 'order'
     * @return array<int, array>
     */
    public function getProducts(array $data = []): array
    {
        $limit = (int) ($data['limit'] ?? 20);
        $offset = (int) ($data['offset'] ?? 0);
        $sort = match ($data['sort'] ?? 'date_added') {
            'name' => 'pd.name',
            'price' => 'p.price',
            'quantity' => 'p.quantity',
            default => 'p.date_added',
        };
        $order = strtoupper($data['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT p.product_id, p.model, p.price, p.quantity, p.image, p.date_added,
                       pd.name
                FROM cc_product p
                LEFT JOIN cc_product_description pd
                    ON (p.product_id = pd.product_id AND pd.language_id = 1)
                WHERE p.status = 1
                ORDER BY {$sort} {$order}
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql);
    }

    /**
     * Get products belonging to a specific category.
     *
     * @return array<int, array>
     */
    public function getProductsByCategory(int $categoryId, array $data = []): array
    {
        $limit = (int) ($data['limit'] ?? 20);
        $offset = (int) ($data['offset'] ?? 0);

        $sql = "SELECT p.product_id, p.model, p.price, p.quantity, p.image,
                       pd.name
                FROM cc_product p
                LEFT JOIN cc_product_description pd
                    ON (p.product_id = pd.product_id AND pd.language_id = 1)
                INNER JOIN cc_product_to_category p2c
                    ON (p.product_id = p2c.product_id)
                WHERE p.status = 1 AND p2c.category_id = :category_id
                ORDER BY p.date_added DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql, [
            'category_id' => $categoryId,
        ]);
    }

    /**
     * Count total products (for pagination).
     */
    public function countProducts(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM cc_product WHERE status = 1");
        return (int) ($result[0]['total'] ?? 0);
    }

    /**
     * Insert a new product with descriptions in a transaction.
     *
     * @param array $product     Product data (model, sku, price, quantity, image, status)
     * @param array $description Descriptions keyed by language_id
     * @return int The new product_id
     */
    public function addProduct(array $product, array $description): int
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
                     VALUES (:product_id, :language_id, :name, :description)",
                    [
                        'product_id'  => $productId,
                        'language_id' => $langId,
                        'name'        => $desc['name'],
                        'description' => $desc['description'] ?? '',
                    ]
                );
            }

            return $productId;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(int $productId, array $product): bool
    {
        return $this->db->execute(
            "UPDATE cc_product
             SET model = :model, sku = :sku, price = :price, quantity = :quantity, image = :image, status = :status
             WHERE product_id = :product_id",
            [
                'product_id' => $productId,
                'model'      => $product['model'],
                'sku'        => $product['sku'] ?? null,
                'price'      => $product['price'] ?? 0,
                'quantity'   => $product['quantity'] ?? 0,
                'image'      => $product['image'] ?? null,
                'status'     => $product['status'] ?? 1,
            ]
        ) > 0;
    }

    /**
     * Delete a product (cascades to descriptions and category links).
     */
    public function deleteProduct(int $productId): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_product WHERE product_id = :product_id",
            ['product_id' => $productId]
        ) > 0;
    }
}
