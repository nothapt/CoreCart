<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\View\TemplateRendererInterface;

class DashboardController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $dashboardService = $this->container->get(\CoreCart\System\Service\DashboardService::class);
        $stats = $dashboardService->getStats();

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $data = [
            'stats'        => $stats,
            'active_menu'  => 'dashboard',
            'csrf_token'   => $session->get('csrf_token', ''),
            'shop_name'    => 'CoreCart',
            'flash_success' => $session->get('flash_success', ''),
            'flash_error'   => $session->get('flash_error', ''),
        ];

        // Remove consumed flash messages
        $session->remove('flash_success');
        $session->remove('flash_error');

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('dashboard/index.html.twig', $data));
    }
}
