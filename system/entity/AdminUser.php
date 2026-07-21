<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Admin User Entity
 */
class AdminUser
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $username = '',
        public readonly string $email = '',
        public readonly int $status = 1,
        public readonly ?string $lastLogin = null,
        public readonly ?string $lastIp = null,
        public readonly string $dateAdded = '',
    ) {}

    public function toArray(): array
    {
        return [
            'admin_id'    => $this->id,
            'username'    => $this->username,
            'email'       => $this->email,
            'status'      => $this->status,
            'last_login'  => $this->lastLogin,
            'last_ip'     => $this->lastIp,
            'date_added'  => $this->dateAdded,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['admin_id'],
            username: $row['username'] ?? '',
            email: $row['email'] ?? '',
            status: (int) ($row['status'] ?? 1),
            lastLogin: $row['last_login'] ?? null,
            lastIp: $row['last_ip'] ?? null,
            dateAdded: $row['date_added'] ?? '',
        );
    }
}
