<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * HTTP Request object
 *
 * Encapsulates all request data. Business logic must not use $_GET/$_POST/$_SERVER/$_SESSION directly.
 */
class Request
{
    private string $method;
    private string $path;
    private array $queryParams;
    private array $body;
    private array $headers;
    private array $cookies;
    private array $files;
    private ?array $user;
    private string $requestId;
    private string $ipAddress;
    private ?string $contentType;
    private int $contentLength;
    private string $bodyRaw;

    public function __construct(
        string $method = 'GET',
        string $path = '/',
        array $queryParams = [],
        array $body = [],
        array $headers = [],
        array $cookies = [],
        array $files = [],
        ?array $user = null,
        string $requestId = ''
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->queryParams = $queryParams;
        $this->body = $body;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->user = $user;
        $this->requestId = $requestId ?: bin2hex(random_bytes(16));
        $this->ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? null;
        $this->contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $this->bodyRaw = file_get_contents('php://input') ?: '';
    }

    /**
     * Build a Request from superglobals (called once at bootstrap).
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

        // Parse body
        $body = $_POST;
        $rawBody = file_get_contents('php://input') ?: '';
        if ($rawBody !== '' && !empty($_SERVER['CONTENT_TYPE'])) {
            $ct = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]));
            if ($ct === 'application/json') {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $body = $decoded;
                }
            }
        }

        $requestId = REQUEST_ID ?? bin2hex(random_bytes(16));

        return new self(
            method: $method,
            path: $path,
            queryParams: $queryParams,
            body: $body,
            headers: $headers,
            cookies: $cookies,
            files: $files,
            user: null,
            requestId: $requestId
        );
    }

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

    public function getBodyRaw(): string
    {
        return $this->bodyRaw;
    }

    public function getHeaders(): array
    {
        return $this->headers;
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

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user ? (int) ($this->user['id'] ?? 0) : null;
    }

    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    public function withUser(array $user): self
    {
        $clone = clone $this;
        $clone->user = $user;
        return $clone;
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
