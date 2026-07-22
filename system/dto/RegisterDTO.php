<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for customer registration
 */
class RegisterDTO
{
    public function __construct(
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstname: trim($data['firstname'] ?? ''),
            lastname: trim($data['lastname'] ?? ''),
            email: trim($data['email'] ?? ''),
            password: $data['password'] ?? '',
        );
    }
}
