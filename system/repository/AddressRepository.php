<?php
declare(strict_types=1);

namespace CoreCart\System\Repository;

use CoreCart\System\Engine\Database;
use CoreCart\System\Entity\Address;

class AddressRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Address
    {
        $result = $this->db->query(
            "SELECT address_id, customer_id, firstname, lastname, address_1, address_2,
                    city, postcode, country, zone, `default`
             FROM cc_address WHERE address_id = :id",
            ['id' => $id]
        );

        return !empty($result) ? Address::fromRow($result[0]) : null;
    }

    public function findByCustomer(int $customerId): array
    {
        $result = $this->db->query(
            "SELECT address_id, customer_id, firstname, lastname, address_1, address_2,
                    city, postcode, country, zone, `default`
             FROM cc_address WHERE customer_id = :cid
             ORDER BY `default` DESC, address_id ASC",
            ['cid' => $customerId]
        );

        return array_map(Address::fromRow(...), $result);
    }

    public function findDefault(int $customerId): ?Address
    {
        $result = $this->db->query(
            "SELECT address_id, customer_id, firstname, lastname, address_1, address_2,
                    city, postcode, country, zone, `default`
             FROM cc_address WHERE customer_id = :cid AND `default` = 1",
            ['cid' => $customerId]
        );

        return !empty($result) ? Address::fromRow($result[0]) : null;
    }

    public function create(int $customerId, array $data): int
    {
        if (!empty($data['default'])) {
            $this->db->execute(
                "UPDATE cc_address SET `default` = 0 WHERE customer_id = :cid",
                ['cid' => $customerId]
            );
        }

        $this->db->execute(
            "INSERT INTO cc_address (customer_id, firstname, lastname, address_1, address_2, city, postcode, country, zone, `default`)
             VALUES (:cid, :fn, :ln, :a1, :a2, :city, :pc, :country, :zone, :def)",
            [
                'cid'     => $customerId,
                'fn'      => $data['firstname'],
                'ln'      => $data['lastname'],
                'a1'      => $data['address_1'],
                'a2'      => $data['address_2'] ?? null,
                'city'    => $data['city'],
                'pc'      => $data['postcode'],
                'country' => $data['country'],
                'zone'    => $data['zone'],
                'def'     => $data['default'] ?? 0,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $customerId, array $data): bool
    {
        if (!empty($data['default'])) {
            $this->db->execute(
                "UPDATE cc_address SET `default` = 0 WHERE customer_id = :cid",
                ['cid' => $customerId]
            );
        }

        return $this->db->execute(
            "UPDATE cc_address
             SET firstname = :fn, lastname = :ln, address_1 = :a1, address_2 = :a2,
                 city = :city, postcode = :pc, country = :country, zone = :zone, `default` = :def
             WHERE address_id = :id AND customer_id = :cid",
            [
                'id'      => $id,
                'cid'     => $customerId,
                'fn'      => $data['firstname'],
                'ln'      => $data['lastname'],
                'a1'      => $data['address_1'],
                'a2'      => $data['address_2'] ?? null,
                'city'    => $data['city'],
                'pc'      => $data['postcode'],
                'country' => $data['country'],
                'zone'    => $data['zone'],
                'def'     => $data['default'] ?? 0,
            ]
        ) > 0;
    }

    public function delete(int $id, int $customerId): bool
    {
        return $this->db->execute(
            "DELETE FROM cc_address WHERE address_id = :id AND customer_id = :cid",
            ['id' => $id, 'cid' => $customerId]
        ) > 0;
    }

    public function countByCustomer(int $customerId): int
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS total FROM cc_address WHERE customer_id = :cid",
            ['cid' => $customerId]
        );
        return (int) ($result[0]['total'] ?? 0);
    }
}
