<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;

class SettingRepository
{
    public function __construct(private Database $db) {}

    public function get(string $group, string $key, string $default = ''): string
    {
        $result = $this->db->query(
            "SELECT value FROM cc_setting WHERE `group` = :group AND `key` = :key LIMIT 1",
            ['group' => $group, 'key' => $key]
        );

        return !empty($result[0]['value']) ? $result[0]['value'] : $default;
    }

    public function getGroup(string $group): array
    {
        $result = $this->db->query(
            "SELECT `key`, value FROM cc_setting WHERE `group` = :group",
            ['group' => $group]
        );

        $settings = [];
        foreach ($result as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public function set(string $group, string $key, string $value): void
    {
        $this->db->execute(
            "INSERT INTO cc_setting (`group`, `key`, value) VALUES (:group, :key, :value)
             ON DUPLICATE KEY UPDATE value = :value2",
            ['group' => $group, 'key' => $key, 'value' => $value, 'value2' => $value]
        );
    }

    public function setGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($group, $key, (string) $value);
        }
    }

    public function delete(string $group, string $key): void
    {
        $this->db->execute(
            "DELETE FROM cc_setting WHERE `group` = :group AND `key` = :key",
            ['group' => $group, 'key' => $key]
        );
    }
}
