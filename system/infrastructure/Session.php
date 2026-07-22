<?php
declare(strict_types=1);

namespace CoreCart\System\Infrastructure;

class Session implements SessionInterface
{
    private string $sessionName;

    public function __construct(string $sessionName = 'CCSESSID')
    {
        $this->sessionName = $sessionName;
        if (session_status() === PHP_SESSION_NONE) {
            $this->configure();
            session_start();
        }
    }

    private function configure(): void
    {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', self::isSecureRequest() ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', '7200');
        ini_set('session.cookie_lifetime', '0');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.name', $this->sessionName);
    }

    /**
     * Detect whether the current request is over HTTPS.
     */
    private static function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $cf = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (is_array($cf) && ($cf['scheme'] ?? '') === 'https') {
                return true;
            }
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function all(): array
    {
        return $_SESSION;
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly'  => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }
        session_destroy();
    }

    public function getId(): string
    {
        return session_id();
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $flashes = $_SESSION['_flash'] ?? [];
        $value = $flashes[$key] ?? $default;
        unset($flashes[$key]);
        $_SESSION['_flash'] = $flashes;
        return $value;
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlashes(): array
    {
        return $_SESSION['_flash'] ?? [];
    }

    public function destroy(): void
    {
        $this->invalidate();
    }
}