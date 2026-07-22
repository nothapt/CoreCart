<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

use CoreCart\System\Infrastructure\AuthenticatedUser;
use CoreCart\System\Infrastructure\SessionInterface;

class AuthMiddleware implements Middleware
{
    private int $lifetime = 7200;
    private int $idleTimeout = 1800;

    public function handle(Request $request, callable $next): Response
    {
        /** @var SessionInterface $session */
        $session = $this->getSession();

        $userId = $session->get('admin_user_id');

        if (!$userId) {
            return new RedirectResponse('/admin/login');
        }

        // Check absolute session lifetime
        $loginTime = $session->get('admin_login_time', 0);
        if (time() - $loginTime > $this->lifetime) {
            $session->invalidate();
            return new RedirectResponse('/admin/login');
        }

        // Check idle timeout
        $lastActivity = $session->get('admin_last_activity', 0);
        if (time() - $lastActivity > $this->idleTimeout) {
            $session->invalidate();
            return new RedirectResponse('/admin/login');
        }

        $session->set('admin_last_activity', time());

        // Verify user still exists and is active
        $db = $this->getDb();
        $result = $db->query(
            "SELECT admin_id, username, email, status FROM cc_admin_user WHERE admin_id = :id",
            ['id' => $userId]
        );

        if (empty($result)) {
            $session->invalidate();
            return new RedirectResponse('/admin/login');
        }

        if ((int) $result[0]['status'] !== 1) {
            $session->invalidate();
            return new RedirectResponse('/admin/login');
        }

        $user = new AuthenticatedUser(
            id: (int) $result[0]['admin_id'],
            username: $result[0]['username'],
            email: $result[0]['email'],
            role: 'admin',
        );

        return $next($request->withUser($user));
    }

    private function getSession(): SessionInterface
    {
        if (isset($GLOBALS['corecart_container'])) {
            return $GLOBALS['corecart_container']->get(SessionInterface::class);
        }
        throw new \RuntimeException('Session not available');
    }

    private function getDb(): Database
    {
        if (isset($GLOBALS['corecart_container'])) {
            return $GLOBALS['corecart_container']->get(Database::class);
        }
        throw new \RuntimeException('Database not available');
    }
}
