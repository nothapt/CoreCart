<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Infrastructure\OrderStatus;

class DashboardController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $dashboardService = $this->container->get(\CoreCart\System\Service\DashboardService::class);
        $stats = $dashboardService->getStats();

        return JsonResponse::success($stats);
    }
}
