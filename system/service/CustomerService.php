<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Repository\AddressRepository;
use CoreCart\System\Entity\Customer;
use CoreCart\System\Entity\Address;
use CoreCart\System\Dto\RegisterDTO;
use CoreCart\System\Dto\AddressDTO;

class CustomerService
{
    private CustomerRepository $customerRepo;
    private AddressRepository $addressRepo;

    public function __construct(CustomerRepository $customerRepo, AddressRepository $addressRepo)
    {
        $this->customerRepo = $customerRepo;
        $this->addressRepo = $addressRepo;
    }

    public function register(RegisterDTO $dto): int
    {
        if (trim($dto->firstname) === '') {
            throw new \InvalidArgumentException('First name is required');
        }
        if (trim($dto->lastname) === '') {
            throw new \InvalidArgumentException('Last name is required');
        }
        if (trim($dto->email) === '' || !filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Valid email is required');
        }
        if (strlen($dto->password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }

        $existing = $this->customerRepo->findByEmail($dto->email);
        if ($existing) {
            throw new \RuntimeException('Email already registered');
        }

        // Auto-generate username from email prefix, ensure uniqueness
        $baseUsername = strtolower(trim(explode('@', $dto->email)[0]));
        $baseUsername = preg_replace('/[^a-z0-9._-]/', '', $baseUsername);
        if ($baseUsername === '') {
            $baseUsername = 'customer';
        }
        $username = $baseUsername;
        $counter = 1;
        while ($this->customerRepo->findByName($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $this->customerRepo->create($username, $dto->firstname, $dto->lastname, $dto->email, $dto->password);
    }

    public function login(string $email, string $password): ?array
    {
        return $this->customerRepo->verifyPassword($email, $password);
    }

    public function getCustomer(int $id): ?Customer
    {
        return $this->customerRepo->findById($id);
    }

    public function getAddresses(int $customerId): array
    {
        return $this->addressRepo->findByCustomer($customerId);
    }

    public function getDefaultAddress(int $customerId): ?Address
    {
        return $this->addressRepo->findDefault($customerId);
    }

    public function addAddress(int $customerId, AddressDTO $dto): int
    {
        $data = [
            'firstname'   => $dto->firstname,
            'lastname'    => $dto->lastname,
            'address_1'   => $dto->address1,
            'address_2'   => $dto->address2,
            'city'        => $dto->city,
            'postcode'    => $dto->postcode,
            'country'     => $dto->country,
            'zone'        => $dto->zone,
            'default'     => $dto->default,
        ];

        return $this->addressRepo->create($customerId, $data);
    }

    public function updateAddress(int $customerId, int $addressId, AddressDTO $dto): bool
    {
        $existing = $this->addressRepo->findById($addressId);
        if (!$existing || $existing->customerId !== $customerId) {
            throw new \RuntimeException('Address not found');
        }

        $data = [
            'firstname'   => $dto->firstname,
            'lastname'    => $dto->lastname,
            'address_1'   => $dto->address1,
            'address_2'   => $dto->address2,
            'city'        => $dto->city,
            'postcode'    => $dto->postcode,
            'country'     => $dto->country,
            'zone'        => $dto->zone,
            'default'     => $dto->default,
        ];

        return $this->addressRepo->update($addressId, $customerId, $data);
    }

    public function deleteAddress(int $customerId, int $addressId): bool
    {
        $existing = $this->addressRepo->findById($addressId);
        if (!$existing || $existing->customerId !== $customerId) {
            throw new \RuntimeException('Address not found');
        }

        return $this->addressRepo->delete($addressId, $customerId);
    }

    public function changePassword(int $customerId, string $newPassword): bool
    {
        if (strlen($newPassword) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }

        return $this->customerRepo->updatePassword($customerId, $newPassword);
    }

    public function getCustomerCount(): int
    {
        return $this->customerRepo->count();
    }
}
