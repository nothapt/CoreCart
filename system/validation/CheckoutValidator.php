<?php
declare(strict_types=1);

namespace CoreCart\System\Validation;

use CoreCart\System\Dto\OrderCreateDTO;

class CheckoutValidator
{
    private array $errors = [];

    private const MAX_EMAIL_LEN = 254;
    private const MAX_NAME_LEN = 128;
    private const MAX_ADDRESS_LEN = 256;
    private const MAX_CITY_LEN = 128;
    private const MAX_POSTCODE_LEN = 32;
    private const MAX_COUNTRY_LEN = 64;
    private const MAX_ZONE_LEN = 64;
    private const MAX_PHONE_LEN = 32;
    private const MAX_COMMENT_LEN = 1000;

    public function validate(OrderCreateDTO $dto): bool
    {
        $this->errors = [];

        $this->requireEmail($dto->customerEmail);
        $this->requireField($dto->shippingFirstname, 'firstname', self::MAX_NAME_LEN);
        $this->requireField($dto->shippingLastname, 'lastname', self::MAX_NAME_LEN);
        $this->requireField($dto->shippingAddress1, 'address_1', self::MAX_ADDRESS_LEN);
        $this->requireField($dto->shippingCity, 'city', self::MAX_CITY_LEN);
        $this->requireField($dto->shippingPostcode, 'postcode', self::MAX_POSTCODE_LEN);
        $this->requireField($dto->shippingCountry, 'country', self::MAX_COUNTRY_LEN);
        $this->requireField($dto->shippingZone, 'zone', self::MAX_ZONE_LEN);
        $this->requireField($dto->customerPhone, 'phone', self::MAX_PHONE_LEN);

        if ($dto->shippingAddress2 !== null && mb_strlen($dto->shippingAddress2) > self::MAX_ADDRESS_LEN) {
            $this->errors['address_2'] = "Address 2 must be at most " . self::MAX_ADDRESS_LEN . " characters";
        }
        if (mb_strlen($dto->comment) > self::MAX_COMMENT_LEN) {
            $this->errors['comment'] = "Comment must be at most " . self::MAX_COMMENT_LEN . " characters";
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): string
    {
        return $this->errors ? reset($this->errors) : '';
    }

    private function requireEmail(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->errors['email'] = 'Email is required';
            return;
        }
        if (mb_strlen($value) > self::MAX_EMAIL_LEN) {
            $this->errors['email'] = 'Email is too long';
            return;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
        }
    }

    private function requireField(?string $value, string $field, int $maxLen): void
    {
        if ($value === null || trim($value) === '') {
            $this->errors[$field] = ucfirst($field) . ' is required';
            return;
        }
        if (mb_strlen($value) > $maxLen) {
            $this->errors[$field] = ucfirst($field) . " must be at most {$maxLen} characters";
        }
    }
}
