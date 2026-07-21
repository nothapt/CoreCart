<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Middleware interface
 *
 * Implement this to create route middleware (auth, CSRF, rate-limiting, etc.).
 * The handle() method must call $next() to continue, or return a response
 * to stop the request chain.
 */
interface Middleware
{
    /**
     * @param callable $next  Call this to pass through to the next middleware/controller
     */
    public function handle(callable $next): void;
}
