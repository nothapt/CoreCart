<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

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

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return JsonResponse::error('Invalid product ID', 400);
        }

        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $product = $catalogService->getProduct($id);

        if (!$product) {
            return JsonResponse::error('Product not found', 404);
        }

        return JsonResponse::success($product->toArray());
    }

    public function search(Request $request): Response
    {
        $query = $request->getQueryParam('q', '');
        if ($query === '') {
            return JsonResponse::error('Search query is required', 400);
        }

        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $data = $catalogService->searchProducts($query, $page);

        return JsonResponse::success($data);
    }
}
