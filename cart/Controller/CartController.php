<?php
declare(strict_types=1);

namespace CoreCart\Cart\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Engine\JsonResponse;
use CoreCart\System\Dto\CartAddDTO;

class CartController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(Request $request): Response
    {
        $sessionId = session_id();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cart = $cartService->getCart($sessionId);

        return JsonResponse::success($cart);
    }

    public function add(Request $request): Response
    {
        $dto = CartAddDTO::fromArray($request->getBody());
        $sessionId = session_id();

        try {
            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartId = $cartService->addItem($sessionId, $dto);
            return JsonResponse::success(['cart_id' => $cartId], 'Item added to cart', 201);
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    public function update(Request $request): Response
    {
        $cartId = (int) $request->getInput('cart_id', 0);
        $quantity = (int) $request->getInput('quantity', 1);
        $sessionId = session_id();

        if ($cartId <= 0) {
            return JsonResponse::error('Invalid cart item ID', 400);
        }

        try {
            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartService->updateQuantity($cartId, $quantity, $sessionId);
            return JsonResponse::success(null, 'Cart updated');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    public function remove(Request $request): Response
    {
        $cartId = (int) $request->getInput('cart_id', $request->getQueryParam('id', 0));
        $sessionId = session_id();

        if ($cartId <= 0) {
            return JsonResponse::error('Invalid cart item ID', 400);
        }

        try {
            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartService->removeItem($cartId, $sessionId);
            return JsonResponse::success(null, 'Item removed from cart');
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        }
    }

    public function clear(Request $request): Response
    {
        $sessionId = session_id();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cartService->clearCart($sessionId);

        return JsonResponse::success(null, 'Cart cleared');
    }

    public function count(Request $request): Response
    {
        $sessionId = session_id();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $count = $cartService->getCartCount($sessionId);

        return JsonResponse::success(['count' => $count]);
    }
}
