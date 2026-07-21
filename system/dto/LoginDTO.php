<?php
declare(strict_types=1);

namespace CoreCart\System\Dto;

/**
 * DTO for admin login
 */
class LoginDTO
{
    public function __construct(
        public readonly string $login,
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            login: trim($data['login'] ?? $data['email'] ?? $data['username'] ?? ''),
            password: $data['password'] ?? '',
        );
    }
}
