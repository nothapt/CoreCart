<?php
declare(strict_types=1);

namespace CoreCart\System\View;

use CoreCart\System\Infrastructure\SessionInterface;

/**
 * Builds shared template context for admin controllers.
 */
class AdminContextProvider
{
    public function __construct(
        private SessionInterface $session,
    ) {}

    /**
     * Build the shared context array for any admin template.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        // Read and consume flash messages
        $flashSuccess = $this->session->get('flash_success', '');
        $flashError = $this->session->get('flash_error', '');
        $this->session->remove('flash_success');
        $this->session->remove('flash_error');

        // Ensure CSRF token exists
        if (!$this->session->has('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

        return [
            'csrf_token'    => $this->session->get('csrf_token', ''),
            'shop_name'     => 'CoreCart',
            'flash_success' => $flashSuccess,
            'flash_error'   => $flashError,
        ];
    }
}
