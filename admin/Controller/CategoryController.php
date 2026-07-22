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

class CategoryController
{
    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->getQueryParam('page', 1));
        $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
        $data = $categoryService->getAllCategories($page);

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        $ctx = [
            'categories'   => $data['categories'] ?? [],
            'total'        => $data['total'] ?? 0,
            'page'         => $data['page'] ?? 1,
            'pages'        => $data['pages'] ?? 1,
            'active_menu'  => 'category',
            'csrf_token'   => $session->get('csrf_token', ''),
            'shop_name'    => 'CoreCart',
        ];

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render('category/list.html.twig', $ctx));
    }

    public function create(Request $request): Response
    {
        $dto = \CoreCart\System\Dto\CategoryDTO::fromArray($request->getBody());

        try {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $categoryService->createCategory($dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Category created');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/category/index');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->getInput('category_id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/category/index');
        }

        $dto = \CoreCart\System\Dto\CategoryDTO::fromArray($request->getBody());

        try {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $categoryService->updateCategory($id, $dto);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Category updated');
        } catch (\InvalidArgumentException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/category/index');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->getInput('category_id', 0);
        if ($id <= 0) {
            return new RedirectResponse('/admin/category/index');
        }

        try {
            $categoryService = $this->container->get(\CoreCart\System\Service\CategoryService::class);
            $categoryService->deleteCategory($id);

            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_success', 'Category deleted');
        } catch (\RuntimeException $e) {
            /** @var SessionInterface $session */
            $session = $this->container->get(SessionInterface::class);
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse('/admin/category/index');
    }
}
