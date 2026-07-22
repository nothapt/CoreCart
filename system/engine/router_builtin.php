<?php
declare(strict_types=1);

/**
 * Built-in PHP server router
 *
 * Used by `php -S localhost:8080 system/engine/router_builtin.php`
 * to simulate Nginx/Apache URL rewriting for local development.
 *
 * Static files: served directly by PHP built-in server.
 * Dynamic routes: forwarded to the correct entry point.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$rootDir = dirname(__DIR__, 2);
$filePath = $rootDir . '/' . ltrim($path, '/');

// Serve static files directly
if (is_file($filePath)) {
    return false;
}

// Route admin requests to admin entry point
if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require_once $rootDir . '/admin/index.php';
    return true;
}

// All other dynamic routes go to storefront entry point
require_once $rootDir . '/index.php';
