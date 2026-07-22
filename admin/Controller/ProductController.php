<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\View\AdminContextProvider;
use CoreCart\System\View\TemplateRendererInterface;

class ProductController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $data = $catalogService->getAllProducts($page);

        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['products'] = $data['products'] ?? [];
        $ctx['total'] = $data['total'] ?? 0;
        $ctx['page'] = $data['page'] ?? 1;
        $ctx['pages'] = $data['pages'] ?? 1;
        $ctx['active_menu'] = 'product';

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('product/list.html.twig', $ctx));
    }

    public function create(Request $request): Response
    {
        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['product'] = [];
        $ctx['active_menu'] = 'product';

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('product/form.html.twig', $ctx));
    }

    public function createPost(Request $request): Response
    {
        $dto = \CoreCart\System\Dto\ProductCreateDTO::fromArray($request->getBody());

        try {
            $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
            $catalogService->createProduct($dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Product created');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/product/index');
    }

    public function edit(Request $request): Response
    {
        $id = (int) $request->getQueryParam('id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/product/index');
        }

        $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
        $product = $catalogService->getProduct($id);

        if (!$product) {
            return new RedirectResponse('/admin/product/index');
        }

        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $ctx = $context->build();
        $ctx['product'] = $product;
        $ctx['active_menu'] = 'product';

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('product/form.html.twig', $ctx));
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->getInput('product_id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/product/index');
        }

        $dto = \CoreCart\System\Dto\ProductUpdateDTO::fromArray($request->getBody());

        try {
            $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
            $catalogService->updateProduct($id, $dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Product updated');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/product/index');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->getInput('product_id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/product/index');
        }

        try {
            $catalogService = $this->container->get(\CoreCart\System\Service\CatalogService::class);
            $catalogService->deleteProduct($id);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Product deleted');
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/product/index');
    }
}
