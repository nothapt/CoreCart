<?php
declare(strict_types=1);

namespace CoreCart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CoreCart\System\Dto\CartAddDTO;
use CoreCart\System\Dto\LoginDTO;
use CoreCart\System\Dto\RegisterDTO;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\Dto\ProductCreateDTO;
use CoreCart\System\Dto\AddressDTO;
use CoreCart\System\Dto\CategoryDTO;

class DtoTest extends TestCase
{
    public function testCartAddDTO(): void
    {
        $dto = CartAddDTO::fromArray(['product_id' => 5, 'quantity' => 3]);
        $this->assertSame(5, $dto->productId);
        $this->assertSame(3, $dto->quantity);
    }

    public function testCartAddDTOQuantityMinOne(): void
    {
        $dto = CartAddDTO::fromArray(['product_id' => 1, 'quantity' => -5]);
        $this->assertSame(1, $dto->quantity);
    }

    public function testLoginDTO(): void
    {
        $dto = LoginDTO::fromArray(['login' => 'admin', 'password' => 'secret']);
        $this->assertSame('admin', $dto->login);
        $this->assertSame('secret', $dto->password);
    }

    public function testLoginDTOLegacyFields(): void
    {
        $dto = LoginDTO::fromArray(['email' => 'test@test.com', 'password' => 'pass']);
        $this->assertSame('test@test.com', $dto->login);
    }

    public function testRegisterDTO(): void
    {
        $dto = RegisterDTO::fromArray(['firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@test.com', 'password' => 'secret123']);
        $this->assertSame('John', $dto->firstname);
        $this->assertSame('Doe', $dto->lastname);
        $this->assertSame('john@test.com', $dto->email);
        $this->assertSame('secret123', $dto->password);
    }

    public function testOrderCreateDTO(): void
    {
        $dto = OrderCreateDTO::fromArray([
            'customer_id' => 1,
            'comment' => 'Please hurry',
            'customer_email' => 'test@test.com',
            'shipping_firstname' => 'John',
            'shipping_lastname' => 'Doe',
            'shipping_address_1' => '123 Main St',
            'shipping_city' => 'NYC',
            'shipping_postcode' => '10001',
            'shipping_country' => 'US',
            'shipping_zone' => 'NY',
        ]);

        $this->assertSame(1, $dto->customerId);
        $this->assertSame('Please hurry', $dto->comment);
        $this->assertSame('test@test.com', $dto->customerEmail);
        $this->assertSame('John', $dto->shippingFirstname);
        $this->assertSame('Doe', $dto->shippingLastname);
        $this->assertSame('123 Main St', $dto->shippingAddress1);
        $this->assertSame('NYC', $dto->shippingCity);
        $this->assertSame('10001', $dto->shippingPostcode);
        $this->assertSame('US', $dto->shippingCountry);
        $this->assertSame('NY', $dto->shippingZone);
    }

    public function testProductCreateDTO(): void
    {
        $dto = ProductCreateDTO::fromArray([
            'model' => 'TEST-01',
            'name' => 'Test Product',
            'price' => '99.99',
            'quantity' => 10,
        ]);

        $this->assertSame('TEST-01', $dto->model);
        $this->assertSame('Test Product', $dto->name);
        $this->assertSame('99.99', $dto->price);
        $this->assertSame(10, $dto->quantity);
    }

    public function testAddressDTO(): void
    {
        $dto = AddressDTO::fromArray([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'address_1' => '123 Main St',
            'city' => 'NYC',
            'postcode' => '10001',
            'country' => 'US',
            'zone' => 'NY',
        ]);

        $this->assertSame('John', $dto->firstname);
        $this->assertSame('Doe', $dto->lastname);
        $this->assertSame('123 Main St', $dto->address1);
        $this->assertSame('NYC', $dto->city);
        $this->assertSame('10001', $dto->postcode);
        $this->assertSame('US', $dto->country);
        $this->assertSame('NY', $dto->zone);
    }

    public function testCategoryDTO(): void
    {
        $dto = CategoryDTO::fromArray(['name' => 'Electronics', 'parent_id' => 2, 'status' => 1]);
        $this->assertSame('Electronics', $dto->name);
        $this->assertSame(2, $dto->parentId);
        $this->assertSame(1, $dto->status);
    }
}
