<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

class RedirectResponse extends Response
{
    public function __construct(string $url, int $statusCode = 302)
    {
        $this->statusCode = $statusCode;
        $this->headers['Location'] = $url;
    }

    /**
     * Redirect back using the Referer header from the request.
     */
    public static function back(Request $request): self
    {
        $referer = $request->getHeader('Referer', '/');
        return new self($referer, 302);
    }

    public static function to(string $url): self
    {
        return new self($url, 302);
    }

    public static function permanent(string $url): self
    {
        return new self($url, 301);
    }
}
