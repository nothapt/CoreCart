<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Rate Limiter
 *
 * Tracks login attempts by IP and login identifier.
 * Blocks after 5 failed attempts within 15 minutes.
 */
class RateLimiter
{
    private Database $db;
    private int $maxAttempts = 5;
    private int $windowSeconds = 900; // 15 minutes

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Check if the given IP or login is rate-limited.
     */
    public function isLimited(string $ipAddress, string $login): bool
    {
        $ipLimited = $this->isIpLimited($ipAddress);
        $loginLimited = $this->isLoginLimited($login);
        return $ipLimited || $loginLimited;
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailure(string $ipAddress, string $login): void
    {
        $this->db->execute(
            "INSERT INTO cc_login_attempt (ip_address, login, success) VALUES (:ip, :login, 0)",
            ['ip' => $ipAddress, 'login' => $login]
        );

        // Log suspicious activity
        $logFile = DIR_LOGS . '/security.log';
        $msg = sprintf(
            "[%s] Failed login attempt: ip=%s login=%s%s",
            date('Y-m-d H:i:s'),
            $ipAddress,
            $login,
            PHP_EOL
        );
        @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * Record a successful login (clears failed attempts for this IP).
     */
    public function recordSuccess(string $ipAddress, string $login): void
    {
        $this->db->execute(
            "DELETE FROM cc_login_attempt WHERE ip_address = :ip AND login = :login",
            ['ip' => $ipAddress, 'login' => $login]
        );
    }

    /**
     * Get remaining seconds until the lockout expires.
     */
    public function getRemainingSeconds(string $ipAddress): int
    {
        $result = $this->db->query(
            "SELECT MIN(date_added) AS first_attempt
             FROM cc_login_attempt
             WHERE ip_address = :ip AND success = 0
               AND date_added > DATE_SUB(NOW(), INTERVAL :window SECOND)",
            ['ip' => $ipAddress, 'window' => $this->windowSeconds]
        );

        if (empty($result[0]['first_attempt'])) {
            return 0;
        }

        $firstAttempt = strtotime($result[0]['first_attempt']);
        $expiresAt = $firstAttempt + $this->windowSeconds;
        $remaining = $expiresAt - time();

        return max(0, $remaining);
    }

    private function isIpLimited(string $ipAddress): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS attempts
             FROM cc_login_attempt
             WHERE ip_address = :ip AND success = 0
               AND date_added > DATE_SUB(NOW(), INTERVAL :window SECOND)",
            ['ip' => $ipAddress, 'window' => $this->windowSeconds]
        );

        return ((int) ($result[0]['attempts'] ?? 0)) >= $this->maxAttempts;
    }

    private function isLoginLimited(string $login): bool
    {
        $result = $this->db->query(
            "SELECT COUNT(*) AS attempts
             FROM cc_login_attempt
             WHERE login = :login AND success = 0
               AND date_added > DATE_SUB(NOW(), INTERVAL :window SECOND)",
            ['login' => $login, 'window' => $this->windowSeconds]
        );

        return ((int) ($result[0]['attempts'] ?? 0)) >= $this->maxAttempts;
    }

    /**
     * Purge old attempts (call periodically).
     */
    public function purge(): void
    {
        $this->db->execute(
            "DELETE FROM cc_login_attempt WHERE date_added < DATE_SUB(NOW(), INTERVAL :window SECOND)",
            ['window' => $this->windowSeconds]
        );
    }
}
