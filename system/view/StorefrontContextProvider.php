<?php
declare(strict_types=1);

namespace CoreCart\System\View;

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
    ) {}

    /**
     * Build the shared context array for any storefront template.
     *
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $userId = $request->getUserId();
        $username = $userId ? $this->session->get('customer_username', '') : '';
        $email = $userId ? $this->session->get('customer_email', '') : '';

        // Build a user-like object for templates: {{ app.user.username }}
        $user = null;
        if ($userId) {
            $user = new \stdClass();
            $user->id = $userId;
            $user->username = $username;
            $user->email = $email;
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

        return [
            'app' => (object) [
                'user'    => $user,
                'request' => $requestAdapter,
            ],
            'csrf_token'    => $this->session->get('csrf_token', ''),
            'shop_name'     => 'CoreCart',
            'flash_success' => $flashSuccess,
            'flash_error'   => $flashError,
        ];
    }
}
