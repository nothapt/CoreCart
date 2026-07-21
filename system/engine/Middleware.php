<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Middleware interface
 *
 * Receives a Request and a callable $next that returns a Response.
 */
interface Middleware
{
    /**
     * @param callable $next  Call this to pass through. Returns Response.
     */
    public function handle(Request $request, callable $next): Response;
}
