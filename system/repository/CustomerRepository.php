<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\Customer;

class CustomerRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Customer
    {
        $result = $this->db->query(
            "SELECT customer_id, username, firstname, lastname, email, status, date_added
             FROM cc_customer WHERE customer_id = :id",
            ['id' => $id]
        );

        return !empty($result) ? Customer::fromRow($result[0]) : null;
    }

    public function findByEmail(string $email): ?Customer
    {
        $result = $this->db->query(
            "SELECT customer_id, username, firstname, lastname, email, status, date_added
             FROM cc_customer WHERE email = :email",
            ['email' => $email]
        );

        return !empty($result) ? Customer::fromRow($result[0]) : null;
    }

    public function findByName(string $username): ?Customer
    {
        $result = $this->db->query(
            "SELECT customer_id, username, firstname, lastname, email, status, date_added
             FROM cc_customer WHERE username = :username",
            ['username' => $username]
        );

        return !empty($result) ? Customer::fromRow($result[0]) : null;
    }

    public function verifyPassword(string $email, string $password): ?array
    {
        $result = $this->db->query(
            "SELECT customer_id, username, firstname, lastname, email, password, status
             FROM cc_customer WHERE email = :email AND status = 1",
            ['email' => $email]
        );

        if (empty($result) || !password_verify($password, $result[0]['password'])) {
            return null;
        }

        return $result[0];
    }

    public function create(string $username, string $firstname, string $lastname, string $email, string $password): int
    {
        $this->db->execute(
            "INSERT INTO cc_customer (username, firstname, lastname, email, password) VALUES (:username, :firstname, :lastname, :email, :password)",
            [
                'username'  => $username,
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'email'     => $email,
                'password'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->db->execute(
            "UPDATE cc_customer SET password = :password WHERE customer_id = :id",
            [
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'id'       => $id,
            ]
        ) > 0;
    }

    public function verifyPasswordById(int $id, string $password): bool
    {
        $result = $this->db->query(
            "SELECT password FROM cc_customer WHERE customer_id = :id",
            ['id' => $id]
        );

        if (empty($result)) {
            return false;
        }

        return password_verify($password, $result[0]['password']);
    }

    public function updateStatus(int $id, int $status): bool
    {
        return $this->db->execute(
            "UPDATE cc_customer SET status = :status WHERE customer_id = :id",
            ['status' => $status, 'id' => $id]
        ) > 0;
    }

    public function count(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM cc_customer");
        return (int) ($result[0]['total'] ?? 0);
    }

    public function findAll(int $limit = 20, int $offset = 0): array
    {
        $result = $this->db->query(
            "SELECT customer_id, username, firstname, lastname, email, status, date_added
             FROM cc_customer
             ORDER BY customer_id DESC
             LIMIT " . (int) $limit . " OFFSET " . (int) $offset
        );

        return array_map(Customer::fromRow(...), $result);
    }
}
