<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * HTTP Response object
 *
 * Encapsulates status code, headers, body, and cookies.
 */
class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $body = '';
    protected array $cookies = [];

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function addCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $sameSite = 'Lax'
    ): self {
        $this->cookies[$name] = [
            'value'    => $value,
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly'  => $httponly,
            'sameSite' => $sameSite,
        ];
        return $this;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Send headers and body to the client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expires'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        echo $this->body;
    }
}
