<?php
declare(strict_types=1);

namespace CoreCart\System\View;

use CoreCart\System\Infrastructure\SessionInterface;

/**
 * Builds shared template context for administration pages.
 */
final class AdminContextProvider
{
    public function __construct(
        private SessionInterface $session,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $flashSuccess = (string) $this->session->get('flash_success', '');
        $flashError = (string) $this->session->get('flash_error', '');

        $this->session->remove('flash_success');
        $this->session->remove('flash_error');

        if (!$this->session->has('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

        return [
            'csrf_token' => (string) $this->session->get('csrf_token', ''),
            'shop_name' => 'CoreCart',
            'admin_username' => (string) $this->session->get('admin_username', 'Administrator'),
            'admin_email' => (string) $this->session->get('admin_email', ''),
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ];
    }
}