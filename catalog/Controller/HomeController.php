<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class HomeController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        /** @var \CoreCart\System\Service\CatalogService $catalogService */
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $products = $catalogService->getActiveProducts(1, 8);

        /** @var \CoreCart\System\Service\CategoryService $categoryService */
        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $categories = $categoryService->getActiveCategories();

        /** @var StorefrontContextProvider $context */
        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['products'] = $products['products'] ?? [];
        $data['categories'] = $categories;

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);

        return new HtmlResponse($renderer->render('home/index.html.twig', $data));
    }
}
