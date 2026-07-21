<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Cart Item Entity
 */
class CartItem
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?int $customerId = null,
        public readonly ?string $sessionId = null,
        public readonly int $productId = 0,
        public readonly int $quantity = 1,
        public readonly string $dateAdded = '',
        public readonly ?string $productName = null,
        public readonly string $productPrice = '0.0000',
        public readonly ?string $productImage = null,
        public readonly int $productQuantity = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'cart_id'           => $this->id,
            'customer_id'       => $this->customerId,
            'session_id'        => $this->sessionId,
            'product_id'        => $this->productId,
            'quantity'          => $this->quantity,
            'date_added'        => $this->dateAdded,
            'product_name'      => $this->productName,
            'product_price'     => $this->productPrice,
            'product_image'     => $this->productImage,
            'product_quantity'  => $this->productQuantity,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['cart_id'],
            customerId: isset($row['customer_id']) ? (int) $row['customer_id'] : null,
            sessionId: $row['session_id'] ?? null,
            productId: (int) $row['product_id'],
            quantity: (int) ($row['quantity'] ?? 1),
            dateAdded: $row['date_added'] ?? '',
            productName: $row['name'] ?? null,
            productPrice: $row['price'] ?? '0.0000',
            productImage: $row['image'] ?? null,
            productQuantity: (int) ($row['p_quantity'] ?? 0),
        );
    }
}
