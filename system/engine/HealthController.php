<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

class HealthController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function live(Request $request): Response
    {
        return new JsonResponse(['status' => 'ok']);
    }

    public function ready(Request $request): Response
    {
        $checks = [];
        $allOk = true;

        // Check database connection
        try {
            $db = $this->container->get(Database::class);
            $db->query("SELECT 1");
            // Verify critical tables exist
            $tables = ['cc_admin_user', 'cc_product', 'cc_category', 'cc_customer', 'cc_order', 'cc_cart'];
            foreach ($tables as $table) {
                $db->query("SELECT 1 FROM `$table` LIMIT 1");
            }
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error';
            $allOk = false;
        }

        // Check installed.lock (informational only, does not affect readiness)
        $checks['installed'] = file_exists(DIR_STORAGE . '/installed.lock') ? 'ok' : 'not installed';

        return new JsonResponse(
            ['status' => $allOk ? 'ok' : 'degraded', 'checks' => $checks],
            $allOk ? 200 : 503
        );
    }
}