<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Category Entity
 */
class Category
{
    public function __construct(
        public readonly int $id = 0,
        public readonly int $parentId = 0,
        public readonly int $status = 1,
        public readonly ?string $name = null,
    ) {}

    public function toArray(): array
    {
        return [
            'category_id' => $this->id,
            'parent_id'   => $this->parentId,
            'status'      => $this->status,
            'name'        => $this->name,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['category_id'],
            parentId: (int) ($row['parent_id'] ?? 0),
            status: (int) ($row['status'] ?? 1),
            name: $row['name'] ?? null,
        );
    }
}
