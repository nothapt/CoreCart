<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Health Check Controller
 *
 * GET /health/live  — Process is running
 * GET /health/ready — Database and config are available
 */
class HealthController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Liveness check: is the PHP process running?
     */
    public function live(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['status' => 'ok'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Readiness check: can the app serve requests?
     */
    public function ready(): void
    {
        $checks = [];

        // Check database
        try {
            $db = $this->container->get(Database::class);
            $db->query("SELECT 1");
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
        }

        // Check .env exists
        $checks['config'] = file_exists(DIR_ROOT . '/.env') ? 'ok' : 'missing';

        // Check installed.lock
        $checks['installed'] = file_exists(DIR_STORAGE . '/installed.lock') ? 'ok' : 'not installed';

        $allOk = !in_array('error', array_map(fn($v) => str_starts_with($v, 'error'), $checks));

        http_response_code($allOk ? 200 : 503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['status' => $allOk ? 'ok' : 'degraded', 'checks' => $checks],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
