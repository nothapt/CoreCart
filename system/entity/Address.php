<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Address Entity
 */
class Address
{
    public function __construct(
        public readonly int $id = 0,
        public readonly int $customerId = 0,
        public readonly string $firstname = '',
        public readonly string $lastname = '',
        public readonly string $address1 = '',
        public readonly ?string $address2 = null,
        public readonly string $city = '',
        public readonly string $postcode = '',
        public readonly string $country = '',
        public readonly string $zone = '',
        public readonly int $default = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'address_id'  => $this->id,
            'customer_id' => $this->customerId,
            'firstname'   => $this->firstname,
            'lastname'    => $this->lastname,
            'address_1'   => $this->address1,
            'address_2'   => $this->address2,
            'city'        => $this->city,
            'postcode'    => $this->postcode,
            'country'     => $this->country,
            'zone'        => $this->zone,
            'default'     => $this->default,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['address_id'],
            customerId: (int) $row['customer_id'],
            firstname: $row['firstname'] ?? '',
            lastname: $row['lastname'] ?? '',
            address1: $row['address_1'] ?? '',
            address2: $row['address_2'] ?? null,
            city: $row['city'] ?? '',
            postcode: $row['postcode'] ?? '',
            country: $row['country'] ?? '',
            zone: $row['zone'] ?? '',
            default: (int) ($row['default'] ?? 0),
        );
    }
}
