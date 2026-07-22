<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\View\TemplateRendererInterface;

class SettingController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $data = [
            'active_menu'  => 'setting',
            'csrf_token'   => $session->get('csrf_token', ''),
            'shop_name'    => 'CoreCart',
        ];

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('setting/index', $data));
    }
}
