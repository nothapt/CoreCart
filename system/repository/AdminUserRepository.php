<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\AdminUser;

class AdminUserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?AdminUser
    {
        $result = $this->db->query(
            "SELECT admin_id, username, email, status, last_login, last_ip, date_added
             FROM cc_admin_user WHERE admin_id = :id",
            ['id' => $id]
        );

        return !empty($result) ? AdminUser::fromRow($result[0]) : null;
    }

    public function findByLogin(string $login): ?AdminUser
    {
        $result = $this->db->query(
            "SELECT admin_id, username, email, password, status, last_login, last_ip, date_added
             FROM cc_admin_user
             WHERE (email = :login_email OR username = :login_user) AND status = 1",
            ['login_email' => $login, 'login_user' => $login]
        );

        return !empty($result) ? AdminUser::fromRow($result[0]) : null;
    }

    public function verifyPassword(string $login, string $password): ?array
    {
        $result = $this->db->query(
            "SELECT admin_id, username, email, password, status
             FROM cc_admin_user
             WHERE (email = :login_email OR username = :login_user) AND status = 1",
            ['login_email' => $login, 'login_user' => $login]
        );

        if (empty($result) || !password_verify($password, $result[0]['password'])) {
            return null;
        }

        return $result[0];
    }

    public function updateLastLogin(int $id, string $ip): void
    {
        $this->db->execute(
            "UPDATE cc_admin_user SET last_login = NOW(), last_ip = :ip WHERE admin_id = :id",
            ['ip' => $ip, 'id' => $id]
        );
    }

    public function create(string $username, string $email, string $password, int $status = 1): int
    {
        $this->db->execute(
            "INSERT INTO cc_admin_user (username, email, password, status)
             VALUES (:username, :email, :password, :status)",
            [
                'username' => $username,
                'email'    => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'status'   => $status,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->db->execute(
            "UPDATE cc_admin_user SET password = :password WHERE admin_id = :id",
            [
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'id'       => $id,
            ]
        ) > 0;
    }

    public function updateStatus(int $id, int $status): bool
    {
        return $this->db->execute(
            "UPDATE cc_admin_user SET status = :status WHERE admin_id = :id",
            ['status' => $status, 'id' => $id]
        ) > 0;
    }

    public function count(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM cc_admin_user");
        return (int) ($result[0]['total'] ?? 0);
    }
}
