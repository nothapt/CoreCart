<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for updating cart quantity
 */
class CartUpdateDTO
{
    public function __construct(
        public readonly int $cartId,
        public readonly int $quantity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cartId: (int) ($data['cart_id'] ?? 0),
            quantity: max(1, (int) ($data['quantity'] ?? 1)),
        );
    }
}
