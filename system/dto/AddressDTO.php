<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for customer address
 */
class AddressDTO
{
    public function __construct(
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $address1,
        public readonly ?string $address2 = null,
        public readonly string $city,
        public readonly string $postcode,
        public readonly string $country,
        public readonly string $zone,
        public readonly int $default = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstname: trim($data['firstname'] ?? ''),
            lastname: trim($data['lastname'] ?? ''),
            address1: trim($data['address_1'] ?? ''),
            address2: $data['address_2'] ?? null,
            city: trim($data['city'] ?? ''),
            postcode: trim($data['postcode'] ?? ''),
            country: trim($data['country'] ?? ''),
            zone: trim($data['zone'] ?? ''),
            default: (int) ($data['default'] ?? 0),
        );
    }
}
