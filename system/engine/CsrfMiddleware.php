<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

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
        return $_SESSION['csrf_token'];
    }

    private function ensureToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function validate(string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
