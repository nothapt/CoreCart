<?php
declare(strict_types=1);

namespace CoreCart\System\Migrations;

interface MigrationInterface
{
    public function getVersion(): string;
    public function getDescription(): string;
    public function up(\PDO $pdo): void;
    public function down(\PDO $pdo): void;
}