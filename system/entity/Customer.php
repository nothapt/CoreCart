<?php
declare(strict_types=1);

namespace CoreCart\System\Entity;

/**
 * Customer Entity
 */
class Customer
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $username = '',
        public readonly string $firstname = '',
        public readonly string $lastname = '',
        public readonly string $email = '',
        public readonly int $status = 1,
        public readonly string $dateAdded = '',
    ) {}

    public function toArray(): array
    {
        return [
            'customer_id' => $this->id,
            'username'    => $this->username,
            'firstname'   => $this->firstname,
            'lastname'    => $this->lastname,
            'email'       => $this->email,
            'status'      => $this->status,
            'date_added'  => $this->dateAdded,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['customer_id'],
            username: $row['username'] ?? '',
            firstname: $row['firstname'] ?? '',
            lastname: $row['lastname'] ?? '',
            email: $row['email'] ?? '',
            status: (int) ($row['status'] ?? 1),
            dateAdded: $row['date_added'] ?? '',
        );
    }
}
