<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;

class ProductController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $data = $catalogService->getActiveProducts($page);

        return JsonResponse::success($data);
    }

    public function create(Request $request): Response
    {
        $dto = \CoreCart\System\Dto\ProductCreateDTO::fromArray($request->getBody());

        try {
            $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
            $id = $catalogService->createProduct($dto);
            return JsonResponse::success(['product_id' => $id], 'Product created', 201);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->getInput('product_id', $request->getQueryParam('id', 0));
        if ($id <= 0) {
            return JsonResponse::error('Invalid product ID', 400);
        }

        $dto = \CoreCart\System\Dto\ProductUpdateDTO::fromArray($request->getBody());

        try {
            $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
            $catalogService->updateProduct($id, $dto);
            return JsonResponse::success(null, 'Product updated');
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 422, 'VALIDATION_ERROR');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->getInput('product_id', $request->getQueryParam('id', 0));
        if ($id <= 0) {
            return JsonResponse::error('Invalid product ID', 400);
        }

        try {
            $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
            $catalogService->deleteProduct($id);
            return JsonResponse::success(null, 'Product deleted');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }
}
