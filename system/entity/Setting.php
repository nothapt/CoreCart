<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

class Setting
{
    public function __construct(
        public readonly int $id,
        public readonly string $group,
        public readonly string $key,
        public readonly string $value,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['setting_id'] ?? 0),
            group: (string) ($row['group'] ?? ''),
            key: (string) ($row['key'] ?? ''),
            value: (string) ($row['value'] ?? ''),
        );
    }
}
