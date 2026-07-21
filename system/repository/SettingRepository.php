<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;

class SettingRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $result = $this->db->query(
            "SELECT setting_value FROM cc_setting WHERE setting_key = :key",
            ['key' => $key]
        );

        return !empty($result[0]['setting_value']) ? $result[0]['setting_value'] : $default;
    }

    public function set(string $key, string $value, string $group = 'config'): void
    {
        $this->db->execute(
            "INSERT INTO cc_setting (setting_key, setting_value, setting_group)
             VALUES (:key, :value, :grp)
             ON DUPLICATE KEY UPDATE setting_value = :value2",
            ['key' => $key, 'value' => $value, 'grp' => $group, 'value2' => $value]
        );
    }

    public function delete(string $key): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_setting WHERE setting_key = :key",
            ['key' => $key]
        ) > 0;
    }

    public function getGroup(string $group): array
    {
        $result = $this->db->query(
            "SELECT setting_key, setting_value FROM cc_setting WHERE setting_group = :grp",
            ['grp' => $group]
        );

        $settings = [];
        foreach ($result as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT setting_key, setting_value, setting_group FROM cc_setting ORDER BY setting_group, setting_key"
        );

        $settings = [];
        foreach ($result as $row) {
            $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
