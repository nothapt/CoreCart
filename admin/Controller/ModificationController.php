<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\View\AdminContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class ModificationController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['active_menu'] = 'modification';

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('modification/index.html.twig', $ctx));
    }
}
