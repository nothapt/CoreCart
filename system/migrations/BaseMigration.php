<?php
declare(strict_types=1);

namespace CoreCart\System\Migrations;

abstract class BaseMigration implements MigrationInterface
{
    protected string $version;
    protected string $description;

    public function __construct()
    {
        $this->version = $this->getVersion();
        $this->description = $this->getDescription();
    }

    abstract public function getVersion(): string;
    abstract public function getDescription(): string;
    abstract public function up(\PDO $pdo): void;
    abstract public function down(\PDO $pdo): void;

    protected function execute(\PDO $pdo, string $sql): void
    {
        $pdo->exec($sql);
    }
}