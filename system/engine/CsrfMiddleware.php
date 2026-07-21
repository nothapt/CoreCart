<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

use CoreCart\System\Infrastructure\SessionInterface;

class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $method = $request->getMethod();

        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $this->ensureToken();
            return $next($request);
        }

        $token = $request->getInput('_csrf_token')
            ?? $request->getHeader('X-CSRF-Token')
            ?? '';

        if (!$this->validate($token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        return $next($request);
    }

    public function getToken(): string
    {
        $this->ensureToken();
        $session = $this->getSession();
        return $session->get('csrf_token');
    }

    private function ensureToken(): void
    {
        $session = $this->getSession();
        if (!$session->has('csrf_token')) {
            $session->set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    private function validate(string $token): bool
    {
        $session = $this->getSession();
        $sessionToken = $session->get('csrf_token');

        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    private function getSession(): SessionInterface
    {
        if (isset($GLOBALS['corecart_container'])) {
            return $GLOBALS['corecart_container']->get(SessionInterface::class);
        }
        throw new \RuntimeException('Session not available');
    }
}
