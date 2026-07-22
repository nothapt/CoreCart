<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Order Item Entity
 */
class OrderItem
{
    public function __construct(
        public readonly int $id = 0,
        public readonly int $orderId = 0,
        public readonly int $productId = 0,
        public readonly string $name = '',
        public readonly int $quantity = 0,
        public readonly string $price = '0.0000',
    ) {}

    public function getTotal(): string
    {
        return (string) bcmul($this->price, (string) $this->quantity, 4);
    }

    public function toArray(): array
    {
        return [
            'order_product_id' => $this->id,
            'order_id'         => $this->orderId,
            'product_id'       => $this->productId,
            'name'             => $this->name,
            'quantity'         => $this->quantity,
            'price'            => $this->price,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['order_product_id'],
            orderId: (int) $row['order_id'],
            productId: (int) $row['product_id'],
            name: $row['name'] ?? '',
            quantity: (int) ($row['quantity'] ?? 0),
            price: $row['price'] ?? '0.0000',
        );
    }
}
