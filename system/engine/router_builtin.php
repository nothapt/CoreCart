<?php
declare(strict_types=1);

/**
 * Built-in PHP server router
 *
 * Used by `php -S localhost:8000 system/engine/router_builtin.php`
 * to simulate Nginx/Apache URL rewriting for local development.
 *
 * If the requested URI points to a real file (image, CSS, JS), serve it directly.
 * Otherwise, route everything through index.php.
 */

$uri = $_SERVER['REQUEST_URI'];
$filePath = dirname(__DIR__, 2) . '/' . ltrim($uri, '/');

// Serve static files directly
if (is_file($filePath)) {
    return false;
}

// Route dynamic requests through the main entry point
$_GET['route'] = ltrim($uri, '/');
require_once dirname(__DIR__, 2) . '/index.php';
