<?php
declare(strict_types=1);

namespace CoreCart\Cart\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Dto\CartAddDTO;
use CoreCart\System\View\StorefrontContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class CartController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cart = $cartService->getCart($sessionId, $customerId);

        $context = $this->container->get(StorefrontContextProvider::class);
        $data = $context->build($request);
        $data['items'] = $cart['items'] ?? [];
        $data['total'] = $cart['total'] ?? '0.00';
        $data['item_count'] = $cart['item_count'] ?? 0;

        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('cart/index.html.twig', $data));
    }

    public function add(Request $request): Response
    {
        $dto = CartAddDTO::fromArray($request->getBody());
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();

        try {
            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartService->addItem($sessionId, $dto, $customerId);
        } catch (\RuntimeException $e) {
            // Store error in session for flash message
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new \CoreCart\System\Engine\RedirectResponse('/cart');
    }

    public function update(Request $request): Response
    {
        $cartId = (int) $request->getInput('cart_id', 0);
        $quantity = (int) $request->getInput('quantity', 1);
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();

        if ($cartId > 0) {
            try {
                $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
                $cartService->updateQuantity($cartId, $quantity, $sessionId, $customerId);
            } catch (\RuntimeException $e) {
                /** @var SessionInterface $session */
                $session = $this->container->get(SessionInterface::class);
                $session->set('flash_error', $e->getMessage());
            }
        }

        return new \CoreCart\System\Engine\RedirectResponse('/cart');
    }

    public function remove(Request $request): Response
    {
        $cartId = (int) $request->getInput('cart_id', 0);
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();

        if ($cartId > 0) {
            try {
                $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
                $cartService->removeItem($cartId, $sessionId, $customerId);
            } catch (\RuntimeException $e) {
                /** @var SessionInterface $session */
                $session = $this->container->get(SessionInterface::class);
                $session->set('flash_error', $e->getMessage());
            }
        }

        return new \CoreCart\System\Engine\RedirectResponse('/cart');
    }

    public function clear(Request $request): Response
    {
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $cartService->clearCart($sessionId, $customerId);

        return new \CoreCart\System\Engine\RedirectResponse('/cart');
    }

    public function count(Request $request): Response
    {
        $sessionId = $this->getSessionId();
        $customerId = $request->getUserId();
        $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
        $count = $cartService->getCartCount($sessionId, $customerId);

        return new \CoreCart\System\Engine\JsonResponse(['data' => ['count' => $count]]);
    }

    public function csrfToken(Request $request): Response
    {
        /** @var \CoreCart\System\Engine\CsrfMiddleware $csrfMiddleware */
        $csrfMiddleware = $this->container->get(\CoreCart\System\Engine\CsrfMiddleware::class);
        return new \CoreCart\System\Engine\JsonResponse(['data' => ['csrf_token' => $csrfMiddleware->getToken()]]);
    }

    private function getSessionId(): string
    {
        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);
        return $session->getId();
    }
}
