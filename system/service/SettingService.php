<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\SettingRepository;

class SettingService
{
    public function __construct(private SettingRepository $repo) {}

    public function get(string $group, string $key, string $default = ''): string
    {
        return $this->repo->get($group, $key, $default);
    }

    public function getGroup(string $group): array
    {
        return $this->repo->getGroup($group);
    }

    public function set(string $group, string $key, string $value): void
    {
        $this->repo->set($group, $key, $value);
    }

    public function setGroup(string $group, array $data): void
    {
        $this->repo->setGroup($group, $data);
    }

    public function delete(string $group, string $key): void
    {
        $this->repo->delete($group, $key);
    }

    public function getStoreName(): string
    {
        return $this->get('store', 'name', 'CoreCart');
    }

    public function getStoreEmail(): string
    {
        return $this->get('store', 'email', '');
    }

    public function getStoreMeta(): array
    {
        return $this->getGroup('meta');
    }

    public function isMaintenanceMode(): bool
    {
        return $this->get('store', 'maintenance', '0') === '1';
    }
}
