<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for adding item to cart
 */
class CartAddDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) ($data['product_id'] ?? 0),
            quantity: max(1, (int) ($data['quantity'] ?? 1)),
        );
    }
}
