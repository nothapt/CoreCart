<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

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
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $data = $categoryService->getAllCategories($page);

        return JsonResponse::success($data);
    }

    public function create(Request $request): Response
    {
        $dto = \CoreCart\System\Dto\CategoryDTO::fromArray($request->getBody());

        try {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $id = $categoryService->createCategory($dto);
            return JsonResponse::success(['category_id' => $id], 'Category created', 201);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->getInput('category_id', $request->getQueryParam('id', 0));
        if ($id <= 0) {
            return JsonResponse::error('Invalid category ID', 400);
        }

        $dto = \CoreCart\System\Dto\CategoryDTO::fromArray($request->getBody());

        try {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $categoryService->updateCategory($id, $dto);
            return JsonResponse::success(null, 'Category updated');
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->getInput('category_id', $request->getQueryParam('id', 0));
        if ($id <= 0) {
            return JsonResponse::error('Invalid category ID', 400);
        }

        try {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $categoryService->deleteCategory($id);
            return JsonResponse::success(null, 'Category deleted');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }
}
