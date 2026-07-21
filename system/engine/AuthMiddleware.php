<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

class AuthMiddleware implements Middleware
{
    private int $lifetime = 7200;
    private int $idleTimeout = 1800;

    public function handle(Request $request, callable $next): Response
    {
        if (empty($_SESSION['admin_user_id'])) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $loginTime = $_SESSION['admin_login_time'] ?? 0;
        if (time() - $loginTime > $this->lifetime) {
            $this->destroySession();
            return new JsonResponse(['error' => 'Session expired'], 401);
        }

        $lastActivity = $_SESSION['admin_last_activity'] ?? 0;
        if (time() - $lastActivity > $this->idleTimeout) {
            $this->destroySession();
            return new JsonResponse(['error' => 'Session expired due to inactivity'], 401);
        }

        $_SESSION['admin_last_activity'] = time();

        $db = $this->getDb();
        $result = $db->query(
            "SELECT admin_id, status FROM cc_admin_user WHERE admin_id = :id",
            ['id' => $_SESSION['admin_user_id']]
        );

        if (empty($result)) {
            $this->destroySession();
            return new JsonResponse(['error' => 'User not found'], 401);
        }

        if ((int) $result[0]['status'] !== 1) {
            $this->destroySession();
            return new JsonResponse(['error' => 'Account disabled'], 401);
        }

        $user = [
            'id'       => (int) $_SESSION['admin_user_id'],
            'username' => $_SESSION['admin_username'] ?? '',
            'email'    => $_SESSION['admin_email'] ?? '',
        ];

        $request = $request->withUser($user);

        return $next($request);
    }

    private function destroySession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private function getDb(): Database
    {
        if (isset($GLOBALS['corecart_container'])) {
            return $GLOBALS['corecart_container']->get(Database::class);
        }
        throw new \RuntimeException('Database not available');
    }
}
