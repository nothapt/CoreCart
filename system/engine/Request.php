<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

use CoreCart\System\Infrastructure\AuthenticatedUser;

/**
 * HTTP Request object — immutable value object
 *
 * Business logic must not use $_GET/$_POST/$_SERVER/$_SESSION directly.
 * Only Request::fromGlobals() reads superglobals.
 */
class Request
{
    public function __construct(
        public readonly string $method = 'GET',
        public readonly string $path = '/',
        public readonly array $queryParams = [],
        public readonly array $body = [],
        public readonly mixed $json = null,
        public readonly array $headers = [],
        public readonly array $cookies = [],
        public readonly array $files = [],
        public readonly ?AuthenticatedUser $user = null,
        public readonly string $requestId = '',
        public readonly string $ipAddress = '0.0.0.0',
        public readonly ?string $contentType = null,
        public readonly int $contentLength = 0,
        public readonly string $rawBody = '',
    ) {}

    /**
     * Build a Request from superglobals. Called once at bootstrap.
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $queryParams = $_GET;
        $files = $_FILES;
        $cookies = $_COOKIE;
        $headers = self::parseHeaders();
        $contentType = $_SERVER['CONTENT_TYPE'] ?? null;
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $rawBody = file_get_contents('php://input') ?: '';

        // Parse body
        $body = $_POST;
        $json = null;
        if ($rawBody !== '' && $contentType !== null) {
            $ct = strtolower(trim(explode(';', $contentType)[0]));
            if ($ct === 'application/json') {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $body = $decoded;
                    $json = $decoded;
                } elseif (json_last_error() === JSON_ERROR_NONE) {
                    $json = $decoded;
                }
            }
        }

        $requestId = defined('REQUEST_ID') ? REQUEST_ID : bin2hex(random_bytes(16));

        return new self(
            method: strtoupper($method),
            path: $path,
            queryParams: $queryParams,
            body: $body,
            json: $json,
            headers: $headers,
            cookies: $cookies,
            files: $files,
            user: null,
            requestId: $requestId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            contentType: $contentType,
            contentLength: $contentLength,
            rawBody: $rawBody,
        );
    }

    // --- Accessors for backward compatibility ---

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getInput(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getJson(): mixed
    {
        return $this->json;
    }

    public function getJsonValue(string $key, mixed $default = null): mixed
    {
        if (!is_array($this->json)) {
            return $default;
        }
        return $this->json[$key] ?? $default;
    }

    public function getBodyRaw(): string
    {
        return $this->rawBody;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $lower = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $lower) {
                return $value;
            }
        }
        return $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getCookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getUploadedFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function getUser(): ?AuthenticatedUser
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user?->id;
    }

    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    /**
     * Return a new Request with the given user attached.
     */
    public function withUser(AuthenticatedUser $user): self
    {
        return new self(
            method: $this->method,
            path: $this->path,
            queryParams: $this->queryParams,
            body: $this->body,
            json: $this->json,
            headers: $this->headers,
            cookies: $this->cookies,
            files: $this->files,
            user: $user,
            requestId: $this->requestId,
            ipAddress: $this->ipAddress,
            contentType: $this->contentType,
            contentLength: $this->contentLength,
            rawBody: $this->rawBody,
        );
    }

    /**
     * Return a new Request with replaced body (e.g. after JSON parse middleware).
     */
    public function withBody(array $body): self
    {
        return new self(
            method: $this->method,
            path: $this->path,
            queryParams: $this->queryParams,
            body: $body,
            json: $this->json,
            headers: $this->headers,
            cookies: $this->cookies,
            files: $this->files,
            user: $this->user,
            requestId: $this->requestId,
            ipAddress: $this->ipAddress,
            contentType: $this->contentType,
            contentLength: $this->contentLength,
            rawBody: $this->rawBody,
        );
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getContentLength(): int
    {
        return $this->contentLength;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    public function wantsJson(): bool
    {
        $accept = $this->getHeader('Accept', '');
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    private static function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }
}
