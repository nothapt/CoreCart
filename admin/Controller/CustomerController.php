<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\View\TemplateRendererInterface;

class CustomerController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $customerRepo = $this->container->get(\CoreCart\System\Repository\CustomerRepository::class);

        $offset = ($page - 1) * 20;
        $customers = $customerRepo->findAll(20, $offset);
        $total = $customerService->getCustomerCount();

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $ctx = [
            'customers'    => $customers,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / 20)),
            'active_menu'  => 'customer',
            'csrf_token'   => $session->get('csrf_token', ''),
            'shop_name'    => 'CoreCart',
        ];

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('customer/list.html.twig', $ctx));
    }

    public function view(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/customer/index');
        }

        $customerService = $this->container->get(\CoreCart\System\Service\CustomerService::class);
        $customer = $customerService->getCustomer($id);

        if (!$customer) {
            return new RedirectResponse('/admin/customer/index');
        }

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $data = [
            'customer'     => $customer,
            'active_menu'  => 'customer',
            'csrf_token'   => $session->get('csrf_token', ''),
            'shop_name'    => 'CoreCart',
        ];

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('customer/view.html.twig', $data));
    }
}
