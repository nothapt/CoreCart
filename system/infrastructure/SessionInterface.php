<?php
declare(strict_types=1);

namespace CoreCart\System\Infrastructure;

interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function remove(string $key): void;
    public function has(string $key): bool;
    public function all(): array;
    public function clear(): void;
    public function regenerate(): void;
    public function invalidate(): void;
    public function getId(): string;
    public function getFlash(string $key, mixed $default = null): mixed;
    public function flash(string $key, mixed $value): void;
    public function getFlashes(): array;
    public function destroy(): void;
}
