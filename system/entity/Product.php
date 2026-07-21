<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Product Entity
 */
class Product
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $model = '',
        public readonly ?string $sku = null,
        public readonly string $price = '0.0000',
        public readonly int $quantity = 0,
        public readonly ?string $image = null,
        public readonly int $status = 1,
        public readonly string $dateAdded = '',
        public readonly ?string $name = null,
        public readonly ?string $description = null,
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->id,
            'model'      => $this->model,
            'sku'        => $this->sku,
            'price'      => $this->price,
            'quantity'   => $this->quantity,
            'image'      => $this->image,
            'status'     => $this->status,
            'date_added' => $this->dateAdded,
            'name'       => $this->name,
            'description' => $this->description,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['product_id'],
            model: $row['model'] ?? '',
            sku: $row['sku'] ?? null,
            price: $row['price'] ?? '0.0000',
            quantity: (int) ($row['quantity'] ?? 0),
            image: $row['image'] ?? null,
            status: (int) ($row['status'] ?? 1),
            dateAdded: $row['date_added'] ?? '',
            name: $row['name'] ?? null,
            description: $row['description'] ?? null,
        );
    }
}
