<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Dependency Injection Container
 *
 * Stores shared services (Database, Session, Config, etc.)
 * and creates them on first access (lazy singleton).
 *
 * Usage:
 *   $container = new Container();
 *   $container->set(Database::class, fn() => new Database());
 *   $db = $container->get(Database::class);
 */
class Container
{
    /** @var array<string, callable> Service factories */
    private array $services = [];

    /** @var array<string, object> Cached singleton instances */
    private array $instances = [];

    /**
     * Register a service factory.
     * The callable receives this Container so it can resolve dependencies.
     */
    public function set(string $name, callable $factory): void
    {
        $this->services[$name] = $factory;
        unset($this->instances[$name]);
    }

    /**
     * Get a service by name.
     * Creates the instance on first call, then returns the same object.
     */
    public function get(string $name): object
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->services[$name])) {
            throw new \RuntimeException("Service '{$name}' is not registered in the container.");
        }

        $this->instances[$name] = ($this->services[$name])($this);
        return $this->instances[$name];
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || isset($this->instances[$name]);
    }
}
