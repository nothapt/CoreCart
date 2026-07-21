<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * CSRF Protection Middleware
 *
 * Generates and validates CSRF tokens for admin POST/PUT/DELETE requests.
 */
class CsrfMiddleware implements Middleware
{
    public function handle(callable $next): void
    {
        // Only check on state-changing methods
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            // Generate token for forms
            $this->ensureToken();
            $next();
            return;
        }

        // Validate token
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->validate($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'Invalid CSRF token'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            return;
        }

        $next();
    }

    /**
     * Get or create the CSRF token for the current session.
     */
    public function getToken(): string
    {
        $this->ensureToken();
        return $_SESSION['csrf_token'];
    }

    /**
     * Generate a new CSRF token if one doesn't exist.
     */
    private function ensureToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Validate a submitted token against the session token.
     */
    private function validate(string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
