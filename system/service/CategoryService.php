<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\CategoryRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Entity\Category;
use CoreCart\System\Dto\CategoryDTO;

class CategoryService
{
    private CategoryRepository $categoryRepo;
    private ProductRepository $productRepo;

    public function __construct(CategoryRepository $categoryRepo, ProductRepository $productRepo)
    {
        $this->categoryRepo = $categoryRepo;
        $this->productRepo = $productRepo;
    }

    public function getCategory(int $id): ?Category
    {
        return $this->categoryRepo->findById($id);
    }

    public function getActiveCategories(): array
    {
        return $this->categoryRepo->findActive();
    }

    public function getAllCategories(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $categories = $this->categoryRepo->findAll($perPage, $offset);
        $total = $this->categoryRepo->count();

        return [
            'categories' => $categories,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'pages'      => (int) ceil($total / $perPage),
        ];
    }

    public function getCategoryTree(): array
    {
        $all = $this->categoryRepo->findActive();
        return $this->buildTree($all, 0);
    }

    public function getChildren(int $parentId): array
    {
        return $this->categoryRepo->findChildren($parentId);
    }

    public function createCategory(CategoryDTO $dto): int
    {
        if (trim($dto->name) === '') {
            throw new \InvalidArgumentException('Category name is required');
        }

        if ($dto->parentId > 0) {
            $parent = $this->categoryRepo->findById($dto->parentId);
            if (!$parent) {
                throw new \RuntimeException('Parent category not found');
            }
        }

        return $this->categoryRepo->create(
            [
                'parent_id' => $dto->parentId,
                'status'    => $dto->status,
            ],
            $dto->name
        );
    }

    public function updateCategory(int $id, CategoryDTO $dto): bool
    {
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            throw new \RuntimeException("Category #{$id} not found");
        }

        if ($dto->parentId === $id) {
            throw new \InvalidArgumentException('Category cannot be its own parent');
        }

        if ($dto->parentId > 0) {
            $parent = $this->categoryRepo->findById($dto->parentId);
            if (!$parent) {
                throw new \RuntimeException('Parent category not found');
            }
        }

        return $this->categoryRepo->update($id, [
            'parent_id' => $dto->parentId,
            'status'    => $dto->status,
            'name'      => $dto->name,
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            throw new \RuntimeException("Category #{$id} not found");
        }

        $children = $this->categoryRepo->findChildren($id);
        if (!empty($children)) {
            throw new \RuntimeException('Cannot delete category with children. Reassign or remove them first.');
        }

        return $this->categoryRepo->delete($id);
    }

    public function getCategoryProductCount(int $categoryId): int
    {
        return $this->categoryRepo->getProductCount($categoryId);
    }

    private function buildTree(array $categories, int $parentId): array
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category->parentId === $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $node = $category->toArray();
                $node['children'] = $children;
                $tree[] = $node;
            }
        }
        return $tree;
    }
}
