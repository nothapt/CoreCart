<?php
declare(strict_types=1);

namespace CoreCart\System\View;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Request;
use CoreCart\System\Infrastructure\SessionInterface;

/**
 * Builds shared template context for storefront controllers.
 *
 * Every storefront controller calls:
 *   $context = $this->context->build($request);
 *   $context['products'] = $products; // controller-specific data
 *   return new HtmlResponse($this->renderer->render('home/index', $context));
 */
class StorefrontContextProvider
{
    public function __construct(
        private SessionInterface $session,
        private Container $container,
    ) {}

    /**
     * Build the shared context array for any storefront template.
     *
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $userId = $request->getUserId();
        $firstname = $userId ? $this->session->get('customer_firstname', '') : '';
        $lastname = $userId ? $this->session->get('customer_lastname', '') : '';
        $email = $userId ? $this->session->get('customer_email', '') : '';
        $username = $userId ? $this->session->get('customer_username', '') : '';

        // Build a user-like object for templates: {{ app.user.firstname }}
        $user = null;
        if ($userId) {
            $user = new \stdClass();
            $user->id = $userId;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->email = $email;
            $user->username = $username;
        }

        // Build a request adapter for templates: {{ app.request.query.get('q') }}
        $requestAdapter = new \stdClass();
        $requestAdapter->query = new class($request->getQueryParams()) {
            private array $params;
            public function __construct(array $params) { $this->params = $params; }
            public function get(string $key, mixed $default = null): mixed
            {
                return $this->params[$key] ?? $default;
            }
        };

        // Read and consume flash messages from session
        $flashSuccess = $this->session->get('flash_success', '');
        $flashError = $this->session->get('flash_error', '');
        $this->session->remove('flash_success');
        $this->session->remove('flash_error');

        // Ensure CSRF token exists
        if (!$this->session->has('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

        // Cart count for header badge
        $cartCount = 0;
        if ($this->container->has(\CoreCart\System\Service\CartService::class)) {
            $sessionId = $this->session->getId();
            $cartService = $this->container->get(\CoreCart\System\Service\CartService::class);
            $cartCount = $cartService->getCartCount($sessionId, $userId);
        }

        return [
            'app' => (object) [
                'user'    => $user,
                'request' => $requestAdapter,
            ],
            'csrf_token'    => $this->session->get('csrf_token', ''),
            'shop_name'     => 'CoreCart',
            'flash_success' => $flashSuccess,
            'flash_error'   => $flashError,
            'cart_count'    => $cartCount,
        ];
    }
}
