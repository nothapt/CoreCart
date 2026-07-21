<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Middleware interface
 *
 * Receives a Request and a callable $next that accepts Request and returns Response.
 */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
