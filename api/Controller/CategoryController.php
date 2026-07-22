<?php
declare(strict_types=1);

namespace CoreCart\Api\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Service\CategoryService;

final class CategoryController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $categoryService = $this->container->get(CategoryService::class);
        $categories = $categoryService->getActiveCategories();

        return JsonResponse::success($categories);
    }

    public function view(Request $request): Response
    {
        $id = (int) ($request->getQuery('id') ?? 0);
        if ($id <= 0) {
            return JsonResponse::error('Category ID is required', 400);
        }

        $categoryService = $this->container->get(CategoryService::class);
        $category = $categoryService->getCategory($id);

        if (!$category) {
            return JsonResponse::error('Category not found', 404);
        }

        return JsonResponse::success($category);
    }
}
