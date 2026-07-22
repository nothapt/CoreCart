<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class ProductController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $data = $catalogService->getActiveProducts($page);

        $context = $this->container->get(StorefrontContextProvider::class);
        $ctx = $context->build($request);
        $ctx['products'] = $data['products'] ?? [];
        $ctx['total'] = $data['total'] ?? 0;
        $ctx['page'] = $data['page'] ?? 1;
        $ctx['pages'] = $data['pages'] ?? 1;
        $ctx['breadcrumbs'] = [['name' => 'Home', 'url' => '/'], ['name' => 'Catalog']];

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('category/index', $ctx));
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return new HtmlResponse('Invalid product ID', 400);
        }

        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $product = $catalogService->getProduct($id);

        if (!$product) {
            return new HtmlResponse('Product not found', 404);
        }

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['product'] = $product;
        $data['breadcrumbs'] = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Catalog', 'url' => '/catalog'],
            ['name' => $product->name],
        ];

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('product/view', $data));
    }

    public function search(Request $request): Response
    {
        $query = $request->getQueryParam('q', '');
        if ($query === '') {
            return $this->index($request);
        }

        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $data = $catalogService->searchProducts($query, $page);

        $context = $this->container->get(StorefrontContextProvider::class);
        $ctx = $context->build($request);
        $ctx['products'] = $data['products'] ?? [];
        $ctx['total'] = $data['total'] ?? 0;
        $ctx['page'] = $data['page'] ?? 1;
        $ctx['pages'] = $data['pages'] ?? 1;
        $ctx['search_query'] = $query;
        $ctx['breadcrumbs'] = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Search: ' . $query],
        ];

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('category/index', $ctx));
    }
}
