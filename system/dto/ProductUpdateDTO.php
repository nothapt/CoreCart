<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for updating a product
 */
class ProductUpdateDTO
{
    public function __construct(
        public readonly string $model,
        public readonly ?string $sku = null,
        public readonly string $price = '0.0000',
        public readonly int $quantity = 0,
        public readonly ?string $image = null,
        public readonly int $status = 1,
        public readonly string $name = '',
        public readonly string $description = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'] ?? '',
            sku: $data['sku'] ?? null,
            price: (string) ($data['price'] ?? '0.0000'),
            quantity: (int) ($data['quantity'] ?? 0),
            image: $data['image'] ?? null,
            status: (int) ($data['status'] ?? 1),
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
        );
    }
}
