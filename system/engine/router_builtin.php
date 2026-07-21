<?php
declare(strict_types=1);

/**
 * Built-in PHP server router
 *
 * Used by `php -S localhost:8000 system/engine/router_builtin.php`
 * to simulate Nginx/Apache URL rewriting for local development.
 *
 * If the requested URI points to a real file (image, CSS, JS), serve it directly.
 * Otherwise, strip query string and route through index.php.
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$filePath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');

// Serve static files directly
if (is_file($filePath)) {
    return false;
}

// Route dynamic requests (strip query string to avoid polluting route)
$route = ltrim($path, '/');
$_GET['route'] = $route !== '' ? $route : 'catalog/home/index';
require_once dirname(__DIR__, 2) . '/index.php';
