<?php
declare(strict_types=1);

namespace CoreCart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CoreCart\System\Engine\Validator;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidData(): void
    {
        $result = $this->validator->validate(
            ['name' => 'John', 'email' => 'john@test.com'],
            ['name' => 'required|string', 'email' => 'required|email']
        );

        $this->assertTrue($result);
        $this->assertEmpty($this->validator->getErrors()['fields']);
    }

    public function testRequiredFieldMissing(): void
    {
        $result = $this->validator->validate(
            ['name' => ''],
            ['name' => 'required']
        );

        $this->assertFalse($result);
        $this->assertArrayHasKey('name', $this->validator->getErrors()['fields']);
    }

    public function testEmailValidation(): void
    {
        $this->assertTrue($this->validator->validate(['email' => 'test@test.com'], ['email' => 'email']));
        $this->assertFalse($this->validator->validate(['email' => 'invalid'], ['email' => 'email']));
    }

    public function testMinLength(): void
    {
        $this->assertTrue($this->validator->validate(['name' => 'John'], ['name' => 'min:3']));
        $this->assertFalse($this->validator->validate(['name' => 'Jo'], ['name' => 'min:3']));
    }

    public function testMaxLength(): void
    {
        $this->assertTrue($this->validator->validate(['name' => 'John'], ['name' => 'max:10']));
        $this->assertFalse($this->validator->validate(['name' => 'Very long name'], ['name' => 'max:5']));
    }

    public function testNumericValidation(): void
    {
        $this->assertTrue($this->validator->validate(['price' => '99.99'], ['price' => 'numeric']));
        $this->assertTrue($this->validator->validate(['price' => '100'], ['price' => 'numeric']));
        $this->assertFalse($this->validator->validate(['price' => 'abc'], ['price' => 'numeric']));
    }

    public function testEnumValidation(): void
    {
        $this->assertTrue($this->validator->validate(['status' => 'active'], ['status' => 'enum:active,inactive']));
        $this->assertFalse($this->validator->validate(['status' => 'deleted'], ['status' => 'enum:active,inactive']));
    }

    public function testMultipleRules(): void
    {
        $result = $this->validator->validate(
            ['email' => '', 'password' => '123'],
            ['email' => 'required|email', 'password' => 'required|min:6']
        );

        $this->assertFalse($result);
        $errors = $this->validator->getErrors()['fields'];
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testErrorFormat(): void
    {
        $this->validator->validate(['name' => ''], ['name' => 'required']);
        $errors = $this->validator->getErrors();

        $this->assertSame('VALIDATION_ERROR', $errors['code']);
        $this->assertSame('Validation failed', $errors['message']);
        $this->assertArrayHasKey('fields', $errors);
    }
}
