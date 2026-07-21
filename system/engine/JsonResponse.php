<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * JSON Response
 *
 * Encodes data as JSON with proper headers.
 */
class JsonResponse extends Response
{
    public function __construct(mixed $data = null, int $statusCode = 200, array $extraHeaders = [])
    {
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->headers['X-Content-Type-Options'] = 'nosniff';

        foreach ($extraHeaders as $name => $value) {
            $this->headers[$name] = $value;
        }

        $this->body = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Shorthand: success response.
     */
    public static function success(mixed $data = null, string $message = 'ok', int $statusCode = 200): self
    {
        return new self([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Shorthand: error response.
     */
    public static function error(string $message, int $statusCode = 400, string $code = 'ERROR'): self
    {
        return new self([
            'status'  => 'error',
            'code'    => $code,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Shorthand: validation error response.
     */
    public static function validationErrors(array $fields, string $message = 'Validation failed'): self
    {
        return new self([
            'code'    => 'VALIDATION_ERROR',
            'message' => $message,
            'fields'  => $fields,
        ], 422);
    }
}
