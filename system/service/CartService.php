<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Entity\CartItem;
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

        if ($product->quantity < $dto->quantity) {
            throw new \RuntimeException('Insufficient stock. Available: ' . $product->quantity);
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

        $product = $this->productRepo->findById($item->productId);
        if ($product && $product->quantity < $quantity) {
            throw new \RuntimeException('Insufficient stock. Available: ' . $product->quantity);
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

    public function getCartTotal(string $sessionId, ?int $customerId = null): string
    {
        if ($customerId) {
            return $this->cartRepo->getTotalByCustomer($customerId);
        }
        return $this->cartRepo->getTotal($sessionId);
    }

    private function verifyOwnership(CartItem $item, string $sessionId, ?int $customerId): void
    {
        if ($customerId) {
            if ($item->customerId !== $customerId) {
                throw new \RuntimeException('Cart item does not belong to this customer');
            }
        } else {
            if ($item->sessionId !== $sessionId) {
                throw new \RuntimeException('Cart item does not belong to this session');
            }
        }
    }
}
