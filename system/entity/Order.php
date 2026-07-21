<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Order Entity
 */
class Order
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?int $customerId = null,
        public readonly int $status = 0,
        public readonly string $total = '0.0000',
        public readonly ?string $comment = null,
        public readonly string $dateAdded = '',
        public readonly string $dateModified = '',
        public readonly ?string $customerName = null,
        public readonly array $items = [],
    ) {}

    public function toArray(): array
    {
        return [
            'order_id'      => $this->id,
            'customer_id'   => $this->customerId,
            'status'        => $this->status,
            'total'         => $this->total,
            'comment'       => $this->comment,
            'date_added'    => $this->dateAdded,
            'date_modified' => $this->dateModified,
            'customer_name' => $this->customerName,
            'items'         => array_map(fn(OrderItem $item) => $item->toArray(), $this->items),
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['order_id'],
            customerId: isset($row['customer_id']) ? (int) $row['customer_id'] : null,
            status: (int) ($row['status'] ?? 0),
            total: $row['total'] ?? '0.0000',
            comment: $row['comment'] ?? null,
            dateAdded: $row['date_added'] ?? '',
            dateModified: $row['date_modified'] ?? '',
            customerName: $row['customer_name'] ?? null,
        );
    }
}
