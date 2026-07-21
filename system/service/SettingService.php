<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\SettingRepository;

class SettingService
{
    private SettingRepository $settingRepo;

    public function __construct(SettingRepository $settingRepo)
    {
        $this->settingRepo = $settingRepo;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->settingRepo->get($key, $default);
    }

    public function set(string $key, string $value, string $group = 'config'): void
    {
        $this->settingRepo->set($key, $value, $group);
    }

    public function getGroup(string $group): array
    {
        return $this->settingRepo->getGroup($group);
    }

    public function getAll(): array
    {
        return $this->settingRepo->getAll();
    }

    public function delete(string $key): bool
    {
        return $this->settingRepo->delete($key);
    }
}
