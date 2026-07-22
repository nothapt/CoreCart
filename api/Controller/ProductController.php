<?php
declare(strict_types=1);

namespace CoreCart\Api\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Service\CatalogService;

final class ProductController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $catalog = $this->container->get(CatalogService::class);
        $products = $catalog->getActiveProducts();

        return JsonResponse::success($products);
    }

    public function view(Request $request): Response
    {
        $id = (int) ($request->getQuery('id') ?? 0);
        if ($id <= 0) {
            return JsonResponse::error('Product ID is required', 400);
        }

        $catalog = $this->container->get(CatalogService::class);
        $product = $catalog->getProduct($id);

        if (!$product) {
            return JsonResponse::error('Product not found', 404);
        }

        return JsonResponse::success($product);
    }

    public function search(Request $request): Response
    {
        $q = $request->getQuery('q') ?? '';
        if ($q === '') {
            return JsonResponse::error('Search query is required', 400);
        }

        $catalog = $this->container->get(CatalogService::class);
        $products = $catalog->searchProducts($q);

        return JsonResponse::success($products);
    }
}
