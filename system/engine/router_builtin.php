<?php
declare(strict_types=1);

/**
 * Built-in PHP server router
 *
 * Used by `php -S localhost:8000 system/engine/router_builtin.php`
 * to simulate Nginx/Apache URL rewriting for local development.
 *
 * Static files: served directly by PHP built-in server.
 * Dynamic routes: forwarded to index.php which reads REQUEST_URI.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$filePath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');

// Serve static files directly
if (is_file($filePath)) {
    return false;
}

// Let index.php handle the route via REQUEST_URI
require_once dirname(__DIR__, 2) . '/index.php';
