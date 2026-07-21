<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for creating an order
 */
class OrderCreateDTO
{
    public function __construct(
        public readonly ?int $customerId = null,
        public readonly ?int $addressId = null,
        public readonly string $comment = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            addressId: isset($data['address_id']) ? (int) $data['address_id'] : null,
            comment: $data['comment'] ?? '',
        );
    }
}
