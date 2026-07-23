<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Service\SettingService;
use CoreCart\System\View\AdminContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class SettingController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        /** @var SettingService $settings */
        $settings = $this->container->get(SettingService::class);

        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['active_menu'] = 'setting';
        $ctx['settings'] = $settings->getGroup('store');
        $ctx['meta'] = $settings->getGroup('meta');
        $ctx['local'] = $settings->getGroup('local');
        $ctx['security'] = $settings->getGroup('security');
        $ctx['mail'] = $settings->getGroup('mail');

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('setting/index.html.twig', $ctx));
    }

    public function store(Request $request): Response
    {
        /** @var SettingService $settings */
        $settings = $this->container->get(SettingService::class);

        $data = [
            'name' => $request->getInput('store_name', 'CoreCart'),
            'owner' => $request->getInput('store_owner', ''),
            'address' => $request->getInput('store_address', ''),
            'email' => $request->getInput('store_email', ''),
            'phone' => $request->getInput('store_phone', ''),
        ];
        $settings->setGroup('store', $data);

        $meta = [
            'title' => $request->getInput('meta_title', ''),
            'description' => $request->getInput('meta_description', ''),
        ];
        $settings->setGroup('meta', $meta);

        $maintenance = $request->getInput('maintenance', '0') === '1' ? '1' : '0';
        $settings->set('store', 'maintenance', $maintenance);

        /** @var \CoreCart\System\Infrastructure\SessionInterface $session */
        $session = $this->container->get(\CoreCart\System\Infrastructure\SessionInterface::class);
        $session->flash('success', 'Store settings saved successfully.');

        return new RedirectResponse('/admin/setting/index');
    }

    public function local(Request $request): Response
    {
        /** @var SettingService $settings */
        $settings = $this->container->get(SettingService::class);

        $data = [
            'language' => $request->getInput('language', 'en-gb'),
            'currency' => $request->getInput('currency', 'USD'),
            'timezone' => $request->getInput('timezone', 'UTC'),
        ];
        $settings->setGroup('local', $data);

        /** @var \CoreCart\System\Infrastructure\SessionInterface $session */
        $session = $this->container->get(\CoreCart\System\Infrastructure\SessionInterface::class);
        $session->flash('success', 'Local settings saved successfully.');

        return new RedirectResponse('/admin/setting/index');
    }

    public function security(Request $request): Response
    {
        /** @var SettingService $settings */
        $settings = $this->container->get(SettingService::class);

        $data = [
            'password_attempts' => $request->getInput('password_attempts', '5'),
            'lockout_duration' => $request->getInput('lockout_duration', '15'),
            'session_timeout' => $request->getInput('session_timeout', '3600'),
        ];
        $settings->setGroup('security', $data);

        /** @var \CoreCart\System\Infrastructure\SessionInterface $session */
        $session = $this->container->get(\CoreCart\System\Infrastructure\SessionInterface::class);
        $session->flash('success', 'Security settings saved successfully.');

        return new RedirectResponse('/admin/setting/index');
    }

    public function mail(Request $request): Response
    {
        /** @var SettingService $settings */
        $settings = $this->container->get(SettingService::class);

        $data = [
            'protocol' => $request->getInput('mail_protocol', 'smtp'),
            'smtp_host' => $request->getInput('smtp_host', ''),
            'smtp_port' => $request->getInput('smtp_port', '587'),
            'smtp_username' => $request->getInput('smtp_username', ''),
            'smtp_password' => $request->getInput('smtp_password', ''),
        ];
        $settings->setGroup('mail', $data);

        /** @var \CoreCart\System\Infrastructure\SessionInterface $session */
        $session = $this->container->get(\CoreCart\System\Infrastructure\SessionInterface::class);
        $session->flash('success', 'Mail settings saved successfully.');

        return new RedirectResponse('/admin/setting/index');
    }
}
