<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class CategoryController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $categories = $categoryService->getActiveCategories();

            $context = $this->container->get(StorefrontContextProvider::class);
            $data = $context->build($request);
            $data['categories'] = $categories;

            $renderer = $this->container->get(TemplateRendererInterface::class);
            return new HtmlResponse($renderer->render('category/index', $data));
        }

        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $category = $categoryService->getCategory($id);

        if (!$category) {
            return new HtmlResponse('Category not found', 404);
        }

        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $products = $catalogService->getProductsByCategory($id, $page);

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['category'] = $category;
        $data['products'] = $products['products'] ?? [];
        $data['total'] = $products['total'] ?? 0;
        $data['page'] = $products['page'] ?? 1;
        $data['pages'] = $products['pages'] ?? 1;
        $data['breadcrumbs'] = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => $category->name],
        ];

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('category/index', $data));
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return new HtmlResponse('Invalid category ID', 400);
        }

        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $category = $categoryService->getCategory($id);

        if (!$category) {
            return new HtmlResponse('Category not found', 404);
        }

        return $this->index($request);
    }
}
