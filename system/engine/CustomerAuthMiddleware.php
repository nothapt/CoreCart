<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

use CoreCart\System\Infrastructure\AuthenticatedUser;
use CoreCart\System\Infrastructure\SessionInterface;

class CustomerAuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        /** @var SessionInterface $session */
        $session = $this->getSession();

        $customerId = $session->get('customer_id');

        if (!$customerId) {
            if ($request->wantsJson()) {
                return new JsonResponse(['error' => 'Authentication required'], 401);
            }
            return RedirectResponse::to('/account/login');
        }

        // Verify user still exists and is active
        $db = $this->getDb();
        $result = $db->query(
            "SELECT customer_id, username, email, status FROM cc_customer WHERE customer_id = :id AND status = 1",
            ['id' => $customerId]
        );

        if (empty($result)) {
            $session->remove('customer_id');
            $session->remove('customer_username');
            $session->remove('customer_email');

            if ($request->wantsJson()) {
                return new JsonResponse(['error' => 'Customer account not found or disabled'], 401);
            }
            return RedirectResponse::to('/account/login');
        }

        $user = new AuthenticatedUser(
            id: (int) $result[0]['customer_id'],
            username: $result[0]['username'],
            email: $result[0]['email'],
            role: 'customer',
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
