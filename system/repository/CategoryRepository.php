<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\Category;

class CategoryRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id, int $languageId = 1): ?Category
    {
        $result = $this->db->query(
            "SELECT c.category_id, c.parent_id, c.status, cd.name
             FROM cc_category c
             LEFT JOIN cc_category_description cd ON (c.category_id = cd.category_id AND cd.language_id = :lang)
             WHERE c.category_id = :id",
            ['id' => $id, 'lang' => $languageId]
        );

        return !empty($result) ? Category::fromRow($result[0]) : null;
    }

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $result = $this->db->query(
            "SELECT c.category_id, c.parent_id, c.status, cd.name
             FROM cc_category c
             LEFT JOIN cc_category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
             ORDER BY c.category_id ASC
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );

        return array_map(Category::fromRow(...), $result);
    }

    public function findActive(): array
    {
        $result = $this->db->query(
            "SELECT c.category_id, c.parent_id, c.status, cd.name
             FROM cc_category c
             LEFT JOIN cc_category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
             WHERE c.status = 1
             ORDER BY c.category_id ASC"
        );

        return array_map(Category::fromRow(...), $result);
    }

    public function findChildren(int $parentId): array
    {
        $result = $this->db->query(
            "SELECT c.category_id, c.parent_id, c.status, cd.name
             FROM cc_category c
             LEFT JOIN cc_category_description cd ON (c.category_id = cd.category_id AND cd.language_id = 1)
             WHERE c.parent_id = :parent_id AND c.status = 1
             ORDER BY c.category_id ASC",
            ['parent_id' => $parentId]
        );

        return array_map(Category::fromRow(...), $result);
    }

    public function create(array $data, string $name): int
    {
        return $this->db->transaction(function ($db) use ($data, $name) {
            $db->execute(
                "INSERT INTO cc_category (parent_id, status) VALUES (:parent_id, :status)",
                [
                    'parent_id' => $data['parent_id'] ?? 0,
                    'status'    => $data['status'] ?? 1,
                ]
            );

            $categoryId = (int) $db->lastInsertId();

            $db->execute(
                "INSERT INTO cc_category_description (category_id, language_id, name) VALUES (:cat_id, 1, :name)",
                ['cat_id' => $categoryId, 'name' => $name]
            );

            return $categoryId;
        });
    }

    public function update(int $id, array $data): bool
    {
        $result = $this->db->execute(
            "UPDATE cc_category SET parent_id = :parent_id, status = :status WHERE category_id = :id",
            [
                'id'        => $id,
                'parent_id' => $data['parent_id'] ?? 0,
                'status'    => $data['status'] ?? 1,
            ]
        );

        if (isset($data['name'])) {
            $this->db->execute(
                "UPDATE cc_category_description SET name = :name WHERE category_id = :id AND language_id = 1",
                ['id' => $id, 'name' => $data['name']]
            );
        }

        return $result > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_category WHERE category_id = :id",
            ['id' => $id]
        ) > 0;
    }

    public function count(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM cc_category");
        return (int) ($result[0]['total'] ?? 0);
    }

    public function getProductCount(int $categoryId): int
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS total FROM cc_product_to_category WHERE category_id = :id",
            ['id' => $categoryId]
        );
        return (int) ($result[0]['total'] ?? 0);
    }
}
