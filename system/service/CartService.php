<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Dto\CartAddDTO;

class CartService
{
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;

    public function __construct(CartRepository $cartRepo, ProductRepository $productRepo)
    {
        $this->cartRepo = $cartRepo;
        $this->productRepo = $productRepo;
    }

    public function getCart(string $sessionId, ?int $customerId = null): array
    {
        $items = $customerId
            ? $this->cartRepo->findByCustomer($customerId)
            : $this->cartRepo->findBySession($sessionId);

        $total = $customerId
            ? $this->cartRepo->getTotalByCustomer($customerId)
            : $this->cartRepo->getTotal($sessionId);

        $count = $customerId
            ? $this->cartRepo->countByCustomer($customerId)
            : $this->cartRepo->count($sessionId);

        return [
            'items' => $items,
            'total' => $total,
            'count' => $count,
        ];
    }

    public function addItem(string $sessionId, CartAddDTO $dto, ?int $customerId = null): int
    {
        $product = $this->productRepo->findById($dto->productId);
        if (!$product) {
            throw new \RuntimeException('Product not found');
        }

        if ($product->status !== 1) {
            throw new \RuntimeException('Product is not available');
        }

        // Check total quantity (existing + new) against stock
        $existing = $customerId
            ? $this->cartRepo->findItemByProductForCustomer($customerId, $dto->productId)
            : $this->cartRepo->findItemByProductForSession($sessionId, $dto->productId);

        $existingQty = $existing ? (int) $existing['quantity'] : 0;
        $totalQty = $existingQty + $dto->quantity;

        if ($totalQty > $product->quantity) {
            throw new \RuntimeException(
                "Insufficient stock for '{$product->name}': requested {$totalQty}, available {$product->quantity}"
            );
        }

        if ($customerId) {
            return $this->cartRepo->addItemForCustomer($customerId, $dto->productId, $dto->quantity);
        }

        return $this->cartRepo->addItem($sessionId, $dto->productId, $dto->quantity);
    }

    public function updateQuantity(int $cartId, int $quantity, string $sessionId, ?int $customerId = null): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $sessionId, $customerId);
        }

        $item = $this->cartRepo->findItem($cartId);
        if (!$item) {
            throw new \RuntimeException('Cart item not found');
        }

        $this->verifyOwnership($item, $sessionId, $customerId);

        $product = $this->productRepo->findById((int) $item['product_id']);
        if ($product && $quantity > $product->quantity) {
            throw new \RuntimeException(
                "Insufficient stock for '{$product->name}': requested {$quantity}, available {$product->quantity}"
            );
        }

        return $this->cartRepo->updateQuantity($cartId, $quantity);
    }

    public function removeItem(int $cartId, string $sessionId, ?int $customerId = null): bool
    {
        $item = $this->cartRepo->findItem($cartId);
        if (!$item) {
            throw new \RuntimeException('Cart item not found');
        }

        $this->verifyOwnership($item, $sessionId, $customerId);

        return $this->cartRepo->removeItem($cartId);
    }

    public function clearCart(string $sessionId, ?int $customerId = null): bool
    {
        if ($customerId) {
            return $this->cartRepo->clearCustomer($customerId);
        }
        return $this->cartRepo->clearSession($sessionId);
    }

    public function mergeGuestToCustomer(string $sessionId, int $customerId): int
    {
        return $this->cartRepo->mergeSessionToCustomer($sessionId, $customerId);
    }

    public function getCartCount(string $sessionId, ?int $customerId = null): int
    {
        if ($customerId) {
            return $this->cartRepo->countByCustomer($customerId);
        }
        return $this->cartRepo->count($sessionId);
    }

    private function verifyOwnership(array $item, string $sessionId, ?int $customerId): void
    {
        if ($customerId) {
            $itemCustomerId = $item['customer_id'] ?? null;
            if ($itemCustomerId === null || (int) $itemCustomerId !== $customerId) {
                throw new \RuntimeException('Cart item does not belong to this customer');
            }
        } else {
            if (($item['session_id'] ?? '') !== $sessionId) {
                throw new \RuntimeException('Cart item does not belong to this session');
            }
        }
    }
}
