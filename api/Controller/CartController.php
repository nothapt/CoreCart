<?php
declare(strict_types=1);

namespace CoreCart\Api\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Service\CartService;
use CoreCart\System\Dto\CartAddDTO;

final class CartController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $cartData = $cart->getCart($sessionId, $customerId);

        return JsonResponse::success(['items' => $cartData['items']]);
    }

    public function count(Request $request): Response
    {
        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $count = $cart->getCartCount($sessionId, $customerId);

        return JsonResponse::success(['count' => $count]);
    }

    public function add(Request $request): Response
    {
        $body = $request->getBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (int) ($body['quantity'] ?? 1);

        if ($productId <= 0) {
            return JsonResponse::error('Product ID is required', 400);
        }

        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $dto = new CartAddDTO(productId: $productId, quantity: $quantity);
        $cart->addItem($sessionId, $dto, $customerId);

        return JsonResponse::success(null, 'Item added to cart');
    }

    public function update(Request $request): Response
    {
        $body = $request->getBody();
        $cartId = (int) ($body['cart_id'] ?? 0);
        $quantity = (int) ($body['quantity'] ?? 1);

        if ($cartId <= 0) {
            return JsonResponse::error('Cart ID is required', 400);
        }

        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $cart->updateQuantity($cartId, $quantity, $sessionId, $customerId);

        return JsonResponse::success(null, 'Cart updated');
    }

    public function remove(Request $request): Response
    {
        $body = $request->getBody();
        $cartId = (int) ($body['cart_id'] ?? 0);

        if ($cartId <= 0) {
            return JsonResponse::error('Cart ID is required', 400);
        }

        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $cart->removeItem($cartId, $sessionId, $customerId);

        return JsonResponse::success(null, 'Item removed');
    }

    public function clear(Request $request): Response
    {
        $sessionId = $this->container->get(SessionInterface::class)->getId();
        $customerId = $request->getUserId();
        $cart = $this->container->get(CartService::class);
        $cart->clearCart($sessionId, $customerId);

        return JsonResponse::success(null, 'Cart cleared');
    }
}
