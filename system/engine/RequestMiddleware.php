<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Request Validator Middleware
 *
 * Checks Content-Type for POST/PUT/PATCH requests.
 * Enforces max request body size.
 */
class RequestMiddleware implements Middleware
{
    private int $maxBodySize;

    public function __construct(int $maxBodySize = 1048576) // 1MB default
    {
        $this->maxBodySize = $maxBodySize;
    }

    public function handle(callable $next): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Check body size
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > $this->maxBodySize) {
            http_response_code(413);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'Request body too large'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            return;
        }

        // Check Content-Type for methods that should have a body
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $contentType = strtolower(trim(explode(';', $contentType)[0]));

            $allowed = ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data'];
            if ($contentType !== '' && !in_array($contentType, $allowed, true)) {
                http_response_code(415);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(
                    ['error' => 'Unsupported content type', 'allowed' => $allowed],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                );
                return;
            }

            // Parse JSON body into $_POST if Content-Type is JSON
            if ($contentType === 'application/json') {
                $raw = file_get_contents('php://input');
                $json = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(
                        ['error' => 'Invalid JSON'],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                    );
                    return;
                }
                $_POST = is_array($json) ? $json : [];
            }
        }

        $next();
    }
}
