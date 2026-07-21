<?php
declare(strict_types=1);

namespace CoreCart\System\Infrastructure;

/**
 * Authenticated user value object
 */
class AuthenticatedUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role,
    ) {}
}
