<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Authentication Middleware
 *
 * Checks that the user is logged in before accessing admin routes.
 * Redirects to admin login page if not authenticated.
 */
class AuthMiddleware implements Middleware
{
    public function handle(callable $next): void
    {
        // Allow the login page itself without auth
        $route = $_GET['route'] ?? '';
        if (str_starts_with($route, 'admin/auth/login')) {
            $next();
            return;
        }

        // Check if admin is logged in
        if (empty($_SESSION['admin_user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'Authentication required'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            return;
        }

        $next();
    }
}
