<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Service\CustomerService;
use CoreCart\System\Service\OrderService;
use CoreCart\System\Service\CartService;
use CoreCart\System\Dto\RegisterDTO;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\Dto\CartAddDTO;
use CoreCart\System\Infrastructure\OrderStatus;
use CoreCart\Tests\Helper\TestDatabase;
use PHPUnit\Framework\TestCase;

class CustomerFlowTest extends TestCase
{
    private TestDatabase $db;
    private CustomerService $customerService;
    private CustomerRepository $customerRepo;

    protected function setUp(): void
    {
        $this->db = new TestDatabase();
        $this->customerRepo = new CustomerRepository($this->db);
        $addressRepo = new \CoreCart\System\Repository\AddressRepository($this->db);
        $this->customerService = new CustomerService($this->customerRepo, $addressRepo);
    }

    public function testRegisterAndLogin(): void
    {
        $dto = new RegisterDTO(
            firstname: 'John',
            lastname: 'Doe',
            email: 'john@example.com',
            password: 'secret123',
        );

        $customerId = $this->customerService->register($dto);
        $this->assertGreaterThan(0, $customerId);

        $result = $this->customerService->login('john@example.com', 'secret123');
        $this->assertNotNull($result);
        $this->assertEquals($customerId, (int) $result['customer_id']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function testLoginWithWrongPassword(): void
    {
        $dto = new RegisterDTO(
            firstname: 'Jane',
            lastname: 'Doe',
            email: 'jane@example.com',
            password: 'correct123',
        );

        $this->customerService->register($dto);

        $result = $this->customerService->login('jane@example.com', 'wrongpassword');
        $this->assertNull($result);
    }

    public function testDuplicateEmailRejected(): void
    {
        $dto = new RegisterDTO(
            firstname: 'User',
            lastname: 'One',
            email: 'dup@example.com',
            password: 'password123',
        );

        $this->customerService->register($dto);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email already registered');

        $this->customerService->register($dto);
    }

    public function testPasswordChange(): void
    {
        $dto = new RegisterDTO(
            firstname: 'Pass',
            lastname: 'Change',
            email: 'pass@example.com',
            password: 'oldpass123',
        );

        $customerId = $this->customerService->register($dto);

        $result = $this->customerService->changePassword($customerId, 'oldpass123', 'newpass456');
        $this->assertTrue($result);

        $login = $this->customerService->login('pass@example.com', 'newpass456');
        $this->assertNotNull($login);

        $oldLogin = $this->customerService->login('pass@example.com', 'oldpass123');
        $this->assertNull($oldLogin);
    }

    public function testPasswordChangeRequiresCurrentPassword(): void
    {
        $dto = new RegisterDTO(
            firstname: 'Secure',
            lastname: 'User',
            email: 'secure@example.com',
            password: 'mypassword',
        );

        $customerId = $this->customerService->register($dto);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Current password is incorrect');

        $this->customerService->changePassword($customerId, 'wrongpassword', 'newpass123');
    }

    public function testGetCustomer(): void
    {
        $dto = new RegisterDTO(
            firstname: 'Get',
            lastname: 'Me',
            email: 'getme@example.com',
            password: 'password123',
        );

        $customerId = $this->customerService->register($dto);
        $customer = $this->customerService->getCustomer($customerId);

        $this->assertNotNull($customer);
        $this->assertEquals('Get', $customer->firstname);
        $this->assertEquals('Me', $customer->lastname);
        $this->assertEquals('getme@example.com', $customer->email);
    }
}
