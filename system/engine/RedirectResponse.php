<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Redirect Response
 */
class RedirectResponse extends Response
{
    public function __construct(string $url, int $statusCode = 302)
    {
        $this->statusCode = $statusCode;
        $this->headers['Location'] = $url;
    }

    /**
     * Shorthand: redirect back.
     */
    public static function back(): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new self($referer, 302);
    }

    /**
     * Shorthand: redirect to URL with 303 (See Other).
     */
    public static function to(string $url): self
    {
        return new self($url, 302);
    }

    /**
     * Shorthand: permanent redirect.
     */
    public static function permanent(string $url): self
    {
        return new self($url, 301);
    }
}
