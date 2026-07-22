<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Lightweight PDO wrapper for database operations.
 *
 * Uses prepared statements by default to prevent SQL injection.
 * Supports transactions for composite operations.
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
        ?string $pass = null,
        ?int $port = null,
    ) {
        $host = $host ?? $_ENV['DB_HOST'] ?? 'localhost';
        $name = $name ?? $_ENV['DB_NAME'] ?? 'corecart';
        $user = $user ?? $_ENV['DB_USER'] ?? 'root';
        $pass = $pass ?? $_ENV['DB_PASS'] ?? '';
        $port = $port ?? (int) ($_ENV['DB_PORT'] ?? 3306);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name,
        );

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
     * Get the last auto-incremented ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Execute a callback inside a transaction.
     * Automatically commits on success, rolls back on exception.
     *
     * @template T
     * @param callable(Database): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Return the raw PDO instance.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
