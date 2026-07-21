<?php
declare(strict_types=1);

namespace CoreCart\System\Route;

use CoreCart\System\Engine\Router;

class HealthRouteProvider
{
    public function register(Router $router): void
    {
        $router->addRoutes([
            'health/live' => [
                'controller' => \CoreCart\System\Engine\HealthController::class,
                'method'     => 'live',
            ],
            'health/ready' => [
                'controller' => \CoreCart\System\Engine\HealthController::class,
                'method'     => 'ready',
            ],
        ]);
    }
}
