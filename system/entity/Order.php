<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

class Order
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?int $customerId = null,
        public readonly int $status = 0,
        public readonly string $total = '0.0000',
        public readonly ?string $comment = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhone = null,
        public readonly ?string $shippingFirstname = null,
        public readonly ?string $shippingLastname = null,
        public readonly ?string $shippingAddress1 = null,
        public readonly ?string $shippingAddress2 = null,
        public readonly ?string $shippingCity = null,
        public readonly ?string $shippingPostcode = null,
        public readonly ?string $shippingCountry = null,
        public readonly ?string $shippingZone = null,
        public readonly string $currencyCode = 'USD',
        public readonly string $currencyValue = '1.0000',
        public readonly string $dateAdded = '',
        public readonly string $dateModified = '',
        public readonly ?string $customerName = null,
        public readonly array $items = [],
    ) {}

    public function toArray(): array
    {
        return [
            'order_id'           => $this->id,
            'customer_id'        => $this->customerId,
            'status'             => $this->status,
            'total'              => $this->total,
            'comment'            => $this->comment,
            'customer_email'     => $this->customerEmail,
            'customer_phone'     => $this->customerPhone,
            'shipping_firstname' => $this->shippingFirstname,
            'shipping_lastname'  => $this->shippingLastname,
            'shipping_address_1' => $this->shippingAddress1,
            'shipping_address_2' => $this->shippingAddress2,
            'shipping_city'      => $this->shippingCity,
            'shipping_postcode'  => $this->shippingPostcode,
            'shipping_country'   => $this->shippingCountry,
            'shipping_zone'      => $this->shippingZone,
            'currency_code'      => $this->currencyCode,
            'currency_value'     => $this->currencyValue,
            'date_added'         => $this->dateAdded,
            'date_modified'      => $this->dateModified,
            'customer_name'      => $this->customerName,
            'items'              => array_map(fn(OrderItem $item) => $item->toArray(), $this->items),
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
            customerEmail: $row['customer_email'] ?? null,
            customerPhone: $row['customer_phone'] ?? null,
            shippingFirstname: $row['shipping_firstname'] ?? null,
            shippingLastname: $row['shipping_lastname'] ?? null,
            shippingAddress1: $row['shipping_address_1'] ?? null,
            shippingAddress2: $row['shipping_address_2'] ?? null,
            shippingCity: $row['shipping_city'] ?? null,
            shippingPostcode: $row['shipping_postcode'] ?? null,
            shippingCountry: $row['shipping_country'] ?? null,
            shippingZone: $row['shipping_zone'] ?? null,
            currencyCode: $row['currency_code'] ?? 'USD',
            currencyValue: $row['currency_value'] ?? '1.0000',
            dateAdded: $row['date_added'] ?? '',
            dateModified: $row['date_modified'] ?? '',
            customerName: $row['customer_name'] ?? null,
        );
    }
}
