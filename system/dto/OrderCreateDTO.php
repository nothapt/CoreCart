<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

class OrderCreateDTO
{
    public function __construct(
        public readonly ?int $customerId = null,
        public readonly ?int $addressId = null,
        public readonly string $comment = '',
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
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            addressId: isset($data['address_id']) ? (int) $data['address_id'] : null,
            comment: $data['comment'] ?? '',
            customerEmail: $data['customer_email'] ?? $data['email'] ?? null,
            customerPhone: $data['customer_phone'] ?? $data['phone'] ?? null,
            shippingFirstname: $data['shipping_firstname'] ?? $data['firstname'] ?? null,
            shippingLastname: $data['shipping_lastname'] ?? $data['lastname'] ?? null,
            shippingAddress1: $data['shipping_address_1'] ?? $data['address_1'] ?? null,
            shippingAddress2: $data['shipping_address_2'] ?? $data['address_2'] ?? null,
            shippingCity: $data['shipping_city'] ?? $data['city'] ?? null,
            shippingPostcode: $data['shipping_postcode'] ?? $data['postcode'] ?? null,
            shippingCountry: $data['shipping_country'] ?? $data['country'] ?? null,
            shippingZone: $data['shipping_zone'] ?? $data['zone'] ?? null,
        );
    }
}
