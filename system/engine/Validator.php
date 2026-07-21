<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Input Validator
 *
 * Validates request data against rules.
 * Returns structured error responses.
 */
class Validator
{
    private array $errors = [];

    /**
     * Validate data against a set of rules.
     *
     * @param array<string, mixed> $data    Input data to validate
     * @param array<string, string> $rules  Rules: 'field' => 'required|string|min:2|max:255'
     * @return bool True if valid
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors in the standard format.
     *
     * @return array{code: string, message: string, fields: array<string, string[]>}
     */
    public function getErrors(): array
    {
        return [
            'code'    => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
            'fields'  => $this->errors,
        ];
    }

    /**
     * Send a 422 JSON response with validation errors.
     */
    public function sendErrors(): void
    {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            $this->getErrors(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    private function applyRule(string $field, mixed $value, string $rule, array $allData): void
    {
        $param = null;
        if (str_contains($rule, ':')) {
            [$rule, $param] = explode(':', $rule, 2);
        }

        match ($rule) {
            'required' => $this->checkRequired($field, $value),
            'string'   => $this->checkString($field, $value),
            'email'    => $this->checkEmail($field, $value),
            'min'      => $this->checkMin($field, $value, (int) $param),
            'max'      => $this->checkMax($field, $value, (int) $param),
            'numeric'  => $this->checkNumeric($field, $value),
            'integer'  => $this->checkInteger($field, $value),
            'enum'     => $this->checkEnum($field, $value, $param),
            'in'       => $this->checkIn($field, $value, $param),
            'url'      => $this->checkUrl($field, $value),
            'date'     => $this->checkDate($field, $value),
            default    => null,
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function checkRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->addError($field, ucfirst($field) . ' is required');
        }
    }

    private function checkString(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_string($value)) {
            $this->addError($field, ucfirst($field) . ' must be a string');
        }
    }

    private function checkEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid email');
        }
    }

    private function checkMin(string $field, mixed $value, int $min): void
    {
        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, ucfirst($field) . " must be at least {$min} characters");
        }
    }

    private function checkMax(string $field, mixed $value, int $max): void
    {
        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, ucfirst($field) . " must be at most {$max} characters");
        }
    }

    private function checkNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, ucfirst($field) . ' must be numeric');
        }
    }

    private function checkInteger(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !ctype_digit((string) $value)) {
            $this->addError($field, ucfirst($field) . ' must be an integer');
        }
    }

    private function checkEnum(string $field, mixed $value, ?string $allowed): void
    {
        if ($value !== null && $value !== '' && $allowed !== null) {
            $options = array_map('trim', explode(',', $allowed));
            if (!in_array($value, $options, true)) {
                $this->addError($field, ucfirst($field) . ' must be one of: ' . implode(', ', $options));
            }
        }
    }

    private function checkIn(string $field, mixed $value, ?string $allowed): void
    {
        $this->checkEnum($field, $value, $allowed);
    }

    private function checkUrl(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid URL');
        }
    }

    private function checkDate(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && strtotime($value) === false) {
            $this->addError($field, ucfirst($field) . ' must be a valid date');
        }
    }
}
