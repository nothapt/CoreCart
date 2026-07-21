<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Authentication Middleware
 *
 * Validates admin session against the database.
 * Checks: session exists, user exists, user is active, session not expired.
 */
class AuthMiddleware implements Middleware
{
    private Database $db;

    /** Session lifetime in seconds (2 hours) */
    private int $lifetime = 7200;

    /** Idle timeout in seconds (30 minutes) */
    private int $idleTimeout = 1800;

    public function __construct()
    {
        // DB will be resolved lazily to avoid circular dependency
    }

    public function handle(callable $next): void
    {
        // Check session
        if (empty($_SESSION['admin_user_id'])) {
            $this->deny('Authentication required');
            return;
        }

        // Check absolute session lifetime
        $loginTime = $_SESSION['admin_login_time'] ?? 0;
        if (time() - $loginTime > $this->lifetime) {
            $this->destroySession();
            $this->deny('Session expired');
            return;
        }

        // Check idle timeout
        $lastActivity = $_SESSION['admin_last_activity'] ?? 0;
        if (time() - $lastActivity > $this->idleTimeout) {
            $this->destroySession();
            $this->deny('Session expired due to inactivity');
            return;
        }

        // Update last activity
        $_SESSION['admin_last_activity'] = time();

        // Verify user still exists and is active in database
        $db = $this->getDb();
        $result = $db->query(
            "SELECT admin_id, status FROM cc_admin_user WHERE admin_id = :id",
            ['id' => $_SESSION['admin_user_id']]
        );

        if (empty($result)) {
            $this->destroySession();
            $this->deny('User not found');
            return;
        }

        if ((int) $result[0]['status'] !== 1) {
            $this->destroySession();
            $this->deny('Account disabled');
            return;
        }

        $next();
    }

    private function deny(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['error' => $message],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    private function destroySession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    private function getDb(): Database
    {
        // Use the global container if available
        if (isset($GLOBALS['corecart_container'])) {
            return $GLOBALS['corecart_container']->get(Database::class);
        }
        throw new \RuntimeException('Database not available in AuthMiddleware');
    }
}
