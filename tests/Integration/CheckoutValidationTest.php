<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Validation\CheckoutValidator;
use CoreCart\System\Dto\OrderCreateDTO;
use PHPUnit\Framework\TestCase;

class CheckoutValidationTest extends TestCase
{
    private CheckoutValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CheckoutValidator();
    }

    private function validDto(): OrderCreateDTO
    {
        return new OrderCreateDTO(
            customerEmail: 'test@example.com',
            customerPhone: '+1234567890',
            shippingFirstname: 'John',
            shippingLastname: 'Doe',
            shippingAddress1: '123 Main St',
            shippingCity: 'New York',
            shippingPostcode: '10001',
            shippingCountry: 'US',
            shippingZone: 'NY',
        );
    }

    private function dtoWith(array $overrides): OrderCreateDTO
    {
        return new OrderCreateDTO(
            customerEmail: array_key_exists('customerEmail', $overrides) ? $overrides['customerEmail'] : 'test@example.com',
            customerPhone: array_key_exists('customerPhone', $overrides) ? $overrides['customerPhone'] : '+1234567890',
            shippingFirstname: array_key_exists('shippingFirstname', $overrides) ? $overrides['shippingFirstname'] : 'John',
            shippingLastname: array_key_exists('shippingLastname', $overrides) ? $overrides['shippingLastname'] : 'Doe',
            shippingAddress1: array_key_exists('shippingAddress1', $overrides) ? $overrides['shippingAddress1'] : '123 Main St',
            shippingCity: array_key_exists('shippingCity', $overrides) ? $overrides['shippingCity'] : 'New York',
            shippingPostcode: array_key_exists('shippingPostcode', $overrides) ? $overrides['shippingPostcode'] : '10001',
            shippingCountry: array_key_exists('shippingCountry', $overrides) ? $overrides['shippingCountry'] : 'US',
            shippingZone: array_key_exists('shippingZone', $overrides) ? $overrides['shippingZone'] : 'NY',
        );
    }

    public function testValidDtoPasses(): void
    {
        $this->assertTrue($this->validator->validate($this->validDto()));
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testMissingEmail(): void
    {
        $dto = $this->dtoWith(['customerEmail' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('email', $this->validator->getErrors());
    }

    public function testInvalidEmail(): void
    {
        $dto = $this->dtoWith(['customerEmail' => 'not-an-email']);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('email', $this->validator->getErrors());
    }

    public function testMissingFirstname(): void
    {
        $dto = $this->dtoWith(['shippingFirstname' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('firstname', $this->validator->getErrors());
    }

    public function testMissingLastname(): void
    {
        $dto = $this->dtoWith(['shippingLastname' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('lastname', $this->validator->getErrors());
    }

    public function testMissingAddress(): void
    {
        $dto = $this->dtoWith(['shippingAddress1' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('address_1', $this->validator->getErrors());
    }

    public function testMissingCity(): void
    {
        $dto = $this->dtoWith(['shippingCity' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('city', $this->validator->getErrors());
    }

    public function testMissingPostcode(): void
    {
        $dto = $this->dtoWith(['shippingPostcode' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('postcode', $this->validator->getErrors());
    }

    public function testMissingCountry(): void
    {
        $dto = $this->dtoWith(['shippingCountry' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('country', $this->validator->getErrors());
    }

    public function testMissingZone(): void
    {
        $dto = $this->dtoWith(['shippingZone' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('zone', $this->validator->getErrors());
    }

    public function testMissingPhone(): void
    {
        $dto = $this->dtoWith(['customerPhone' => null]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('phone', $this->validator->getErrors());
    }

    public function testFirstnameTooLong(): void
    {
        $dto = $this->dtoWith(['shippingFirstname' => str_repeat('A', 129)]);
        $this->assertFalse($this->validator->validate($dto));
        $this->assertArrayHasKey('firstname', $this->validator->getErrors());
    }

    public function testGetFirstError(): void
    {
        $dto = new OrderCreateDTO();
        $this->assertFalse($this->validator->validate($dto));
        $first = $this->validator->getFirstError();
        $this->assertNotEmpty($first);
    }
}
