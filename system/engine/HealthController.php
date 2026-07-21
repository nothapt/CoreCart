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

        try {
            $db = $this->container->get(Database::class);
            $db->query("SELECT 1");
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
        }

        $checks['config'] = file_exists(DIR_ROOT . '/.env') ? 'ok' : 'missing';
        $checks['installed'] = file_exists(DIR_STORAGE . '/installed.lock') ? 'ok' : 'not installed';

        $allOk = !in_array('error', array_map(fn($v) => str_starts_with($v, 'error'), $checks));

        return new JsonResponse(
            ['status' => $allOk ? 'ok' : 'degraded', 'checks' => $checks],
            $allOk ? 200 : 503
        );
    }
}
