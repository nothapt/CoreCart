<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for category create/update
 */
class CategoryDTO
{
    public function __construct(
        public readonly int $parentId = 0,
        public readonly int $status = 1,
        public readonly string $name = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            parentId: (int) ($data['parent_id'] ?? 0),
            status: (int) ($data['status'] ?? 1),
            name: $data['name'] ?? '',
        );
    }
}
