<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\HtmlResponse;
use CoreCart\System\Engine\RedirectResponse;
use CoreCart\System\Engine\Request;
use CoreCart\System\Engine\Response;
use CoreCart\System\Infrastructure\SessionInterface;
use CoreCart\System\Service\ThemeEditorService;
use CoreCart\System\View\AdminContextProvider;
use CoreCart\System\View\TemplateRendererInterface;
use CoreCart\System\View\TwigRenderer;

final class ThemeEditorController
{
    private const AREAS = ['catalog', 'admin'];

    private ?ThemeEditorService $themeEditorService = null;

    public function __construct(
        private Container $container,
    ) {}

    private function getThemeEditorService(): ThemeEditorService
    {
        if ($this->themeEditorService === null) {
            /** @var TwigRenderer $twigRenderer */
            $twigRenderer = $this->container->get(TemplateRendererInterface::class);
            $this->themeEditorService = new ThemeEditorService($twigRenderer->getTwig());
        }
        return $this->themeEditorService;
    }

    public function index(Request $request): Response
    {
        $service = $this->getThemeEditorService();

        $area = $this->normalizeArea((string) $request->getQueryParam('area', 'catalog'));
        $files = $this->listTemplates($area);

        $selected = (string) $request->getQueryParam('file', '');
        if ($selected === '' || !in_array($selected, $files, true)) {
            $selected = $files[0] ?? '';
        }

        $content = '';
        if ($selected !== '') {
            $content = $service->readFile($area, $selected);
        }

        /** @var AdminContextProvider $context */
        $context = $this->container->get(AdminContextProvider::class);
        $data = $context->build();
        $data['active_menu'] = 'theme_editor';
        $data['area'] = $area;
        $data['files'] = $files;
        $data['selected_file'] = $selected;
        $data['content'] = $content;

        /** @var TemplateRendererInterface $renderer */
        $renderer = $this->container->get(TemplateRendererInterface::class);

        return new HtmlResponse(
            $renderer->render('design/theme_editor.html.twig', $data)
        );
    }

    public function save(Request $request): Response
    {
        $service = $this->getThemeEditorService();

        $area = $this->normalizeArea((string) $request->getInput('area', 'catalog'));
        $file = (string) $request->getInput('file', '');
        $action = (string) $request->getInput('action', 'save');

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        try {
            if ($action === 'reset') {
                $backups = $service->getBackups($area, $file);
                if (empty($backups)) {
                    throw new \RuntimeException('No backup exists for this template');
                }
                $service->restoreBackup($area, $file, $backups[0]);
                $session->set('flash_success', 'Latest template backup restored');
            } else {
                $content = (string) $request->getInput('content', '');
                $service->saveFile($area, $file, $content);
                $session->set('flash_success', 'Template saved');
            }

            $this->clearTwigCache();
        } catch (\Throwable $e) {
            $session->set('flash_error', $e->getMessage());
        }

        return new RedirectResponse(
            '/admin/design/theme-editor?' . http_build_query([
                'area' => $area,
                'file' => $file,
            ])
        );
    }

    private function normalizeArea(string $area): string
    {
        return in_array($area, self::AREAS, true) ? $area : 'catalog';
    }

    private function templateRoot(string $area): string
    {
        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . $area
            . DIRECTORY_SEPARATOR
            . 'View'
            . DIRECTORY_SEPARATOR
            . 'theme'
            . DIRECTORY_SEPARATOR
            . 'default'
            . DIRECTORY_SEPARATOR
            . 'template';
    }

    /**
     * @return string[]
     */
    private function listTemplates(string $area): array
    {
        $root = $this->templateRoot($area);
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $root,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo || !$item->isFile()) {
                continue;
            }

            if (!str_ends_with(strtolower($item->getFilename()), '.twig')) {
                continue;
            }

            $relative = substr($item->getPathname(), strlen($root) + 1);
            $files[] = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        }

        natcasesort($files);

        return array_values($files);
    }

    private function clearTwigCache(): void
    {
        $cache = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'twig';

        if (!is_dir($cache)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $cache,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }
}
