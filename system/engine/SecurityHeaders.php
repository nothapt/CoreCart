<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Security Headers Middleware
 *
 * Adds standard security headers to every response.
 */
class SecurityHeaders implements Middleware
{
    public function handle(callable $next): void
    {
        $next();

        // Add headers after the response is generated
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\'');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
