<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

final class HtmlResponse extends Response
{
    public function __construct(
        string $html,
        int $statusCode = 200,
        array $headers = [],
    ) {
        $this->setBody($html);
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }
}
