<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Lightweight PDO wrapper for database operations.
 *
 * Uses prepared statements by default to prevent SQL injection.
 * All queries go through this single class.
 */
class Database
{
    private \PDO $pdo;

    /**
     * Connect to the database using environment variables or explicit params.
     */
    public function __construct(
        ?string $host = null,
        ?string $name = null,
        ?string $user = null,
        ?string $pass = null
    ) {
        $host = $host ?? $_ENV['DB_HOST'] ?? 'localhost';
        $name = $name ?? $_ENV['DB_NAME'] ?? 'corecart';
        $user = $user ?? $_ENV['DB_USER'] ?? 'root';
        $pass = $pass ?? $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new \PDO($dsn, $user, $pass, $options);
    }

    /**
     * Run a SELECT query and return all matching rows.
     *
     * @param string $sql  SQL query with named or positional placeholders
     * @param array  $params  Bound parameters
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run an INSERT, UPDATE, or DELETE query.
     *
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get the last auto-incremented ID (useful after INSERT).
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Return the raw PDO instance if you need advanced features.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
