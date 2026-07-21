<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;

class CategoryController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $categories = $categoryService->getActiveCategories();

        return JsonResponse::success(array_map(fn($c) => $c->toArray(), $categories));
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return JsonResponse::error('Invalid category ID', 400);
        }

        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $category = $categoryService->getCategory($id);

        if (!$category) {
            return JsonResponse::error('Category not found', 404);
        }

        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $products = $catalogService->getProductsByCategory($id, $page);

        return JsonResponse::success([
            'category' => $category->toArray(),
            'products' => $products,
        ]);
    }
}
