<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Entity\Product;
use CoreCart\System\Dto\ProductCreateDTO;
use CoreCart\System\Dto\ProductUpdateDTO;

class CatalogService
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    public function getProduct(int $id, int $languageId = 1): ?Product
    {
        return $this->productRepo->findById($id, $languageId);
    }

    public function getActiveProducts(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $products = $this->productRepo->findActive($perPage, $offset);
        $total = $this->productRepo->countActive();

        return [
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
        ];
    }

    public function getProductsByCategory(int $categoryId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $products = $this->productRepo->findByCategory($categoryId, $perPage, $offset);
        $total = $this->productRepo->countByCategory($categoryId);

        return [
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
        ];
    }

    public function searchProducts(string $query, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $products = $this->productRepo->search($query, $perPage, $offset);

        return [
            'products' => $products,
            'query'    => $query,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    public function createProduct(ProductCreateDTO $dto): int
    {
        $this->validateProductData($dto->model, $dto->price, $dto->quantity);

        return $this->productRepo->create(
            [
                'model'    => $dto->model,
                'sku'      => $dto->sku,
                'price'    => $dto->price,
                'quantity' => $dto->quantity,
                'image'    => $dto->image,
                'status'   => $dto->status,
            ],
            [
                1 => [
                    'name'        => $dto->name,
                    'description' => $dto->description,
                ],
            ]
        );
    }

    public function updateProduct(int $id, ProductUpdateDTO $dto): bool
    {
        $product = $this->productRepo->findById($id);
        if (!$product) {
            throw new \RuntimeException("Product #{$id} not found");
        }

        $this->validateProductData($dto->model, $dto->price, $dto->quantity);

        return $this->productRepo->update($id, [
            'model'       => $dto->model,
            'sku'         => $dto->sku,
            'price'       => $dto->price,
            'quantity'    => $dto->quantity,
            'image'       => $dto->image,
            'status'      => $dto->status,
            'name'        => $dto->name,
            'description' => $dto->description,
        ]);
    }

    public function deleteProduct(int $id): bool
    {
        $product = $this->productRepo->findById($id);
        if (!$product) {
            throw new \RuntimeException("Product #{$id} not found");
        }

        return $this->productRepo->delete($id);
    }

    public function checkStock(int $productId, int $quantity): bool
    {
        $product = $this->productRepo->findById($productId);
        if (!$product) {
            return false;
        }
        return $product->quantity >= $quantity;
    }

    private function validateProductData(string $model, string $price, int $quantity): void
    {
        if (trim($model) === '') {
            throw new \InvalidArgumentException('Product model is required');
        }
        if (!is_numeric($price) || (float) $price < 0) {
            throw new \InvalidArgumentException('Product price must be a non-negative number');
        }
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Product quantity must be non-negative');
        }
    }
}
