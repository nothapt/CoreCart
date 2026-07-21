<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Database;
use CoreCart\System\Engine\Validator;
use CoreCart\System\Engine\RateLimiter;

/**
 * Admin Auth Controller
 *
 * Handles login, logout, and CSRF token endpoint.
 */
class AuthController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * GET /admin/auth/login — Show login form (or redirect if already logged in)
     */
    public function login(): void
    {
        if (!empty($_SESSION['admin_user_id'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['message' => 'Already logged in'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['message' => 'Login form', 'csrf_token' => $_SESSION['csrf_token'] ?? ''],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * POST /admin/auth/login — Process login
     */
    public function loginPost(): void
    {
        $db = $this->container->get(Database::class);
        $rateLimiter = $this->container->get(RateLimiter::class);
        $validator = $this->container->get(Validator::class);

        $login = trim($_POST['login'] ?? $_POST['email'] ?? $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        $validator->validate($_POST, [
            'login'    => 'required|string|min:2|max:255',
            'password' => 'required|string|min:6',
        ]);

        if (!empty($validator->getErrors()['fields'])) {
            $validator->sendErrors();
            return;
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Check rate limit
        if ($rateLimiter->isLimited($ipAddress, $login)) {
            $remaining = $rateLimiter->getRemainingSeconds($ipAddress);
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                [
                    'error'   => 'Too many login attempts',
                    'message' => "Try again in {$remaining} seconds",
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            return;
        }

        // Find user by email or username (use two params: native PDO doesn't allow reusing placeholders)
        $result = $db->query(
            "SELECT admin_id, username, email, password, status
             FROM cc_admin_user
             WHERE (email = :login_email OR username = :login_user) AND status = 1",
            ['login_email' => $login, 'login_user' => $login]
        );

        if (empty($result) || !password_verify($password, $result[0]['password'])) {
            $rateLimiter->recordFailure($ipAddress, $login);
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'Invalid credentials'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            return;
        }

        // Login successful
        $rateLimiter->recordSuccess($ipAddress, $login);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['admin_user_id'] = (int) $result[0]['admin_id'];
        $_SESSION['admin_username'] = $result[0]['username'];
        $_SESSION['admin_email'] = $result[0]['email'];
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_ip'] = $ipAddress;

        // Update last login in DB
        $db->execute(
            "UPDATE cc_admin_user SET last_login = NOW(), last_ip = :ip WHERE admin_id = :id",
            ['ip' => $ipAddress, 'id' => $result[0]['admin_id']]
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            [
                'status'  => 'success',
                'message' => 'Login successful',
                'user'    => [
                    'id'       => (int) $result[0]['admin_id'],
                    'username' => $result[0]['username'],
                    'email'    => $result[0]['email'],
                ],
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * POST /admin/auth/logout — Destroy session and logout
     */
    public function logout(): void
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

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['status' => 'success', 'message' => 'Logged out'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * GET /admin/csrf-token — Return the current CSRF token
     */
    public function csrfToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['data' => ['csrf_token' => $_SESSION['csrf_token']]],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
