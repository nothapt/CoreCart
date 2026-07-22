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

final class ThemeEditorController
{
    private const AREAS = ['catalog', 'admin'];

    public function __construct(
        private Container $container,
    ) {}

    public function index(Request $request): Response
    {
        $area = $this->normalizeArea((string) $request->getQueryParam('area', 'catalog'));
        $files = $this->listTemplates($area);

        $selected = (string) $request->getQueryParam('file', '');
        if ($selected === '' || !in_array($selected, $files, true)) {
            $selected = $files[0] ?? '';
        }

        $content = '';
        if ($selected !== '') {
            $path = $this->resolveTemplate($area, $selected);
            $read = file_get_contents($path);
            if ($read === false) {
                throw new \RuntimeException('Unable to read template');
            }
            $content = $read;
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
        $area = $this->normalizeArea((string) $request->getInput('area', 'catalog'));
        $file = (string) $request->getInput('file', '');
        $action = (string) $request->getInput('action', 'save');

        /** @var SessionInterface $session */
        $session = $this->container->get(SessionInterface::class);

        try {
            $path = $this->resolveTemplate($area, $file);

            if ($action === 'reset') {
                $this->restoreLatestBackup($area, $file, $path);
                $session->set('flash_success', 'Latest template backup restored');
            } else {
                $content = (string) $request->getInput('content', '');

                $this->backupTemplate($area, $file, $path);

                if (file_put_contents($path, $content, LOCK_EX) === false) {
                    throw new \RuntimeException('Unable to save template');
                }

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

    private function resolveTemplate(string $area, string $file): string
    {
        if (
            $file === ''
            || str_contains($file, "\0")
            || str_contains($file, '..')
            || !str_ends_with(strtolower($file), '.twig')
        ) {
            throw new \InvalidArgumentException('Invalid template path');
        }

        $root = realpath($this->templateRoot($area));
        if ($root === false) {
            throw new \RuntimeException('Theme directory not found');
        }

        $candidate = realpath(
            $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file)
        );

        if (
            $candidate === false
            || !is_file($candidate)
            || !str_starts_with($candidate, $root . DIRECTORY_SEPARATOR)
        ) {
            throw new \RuntimeException('Template not found');
        }

        return $candidate;
    }

    private function backupTemplate(string $area, string $file, string $path): void
    {
        $backupRoot = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'backups'
            . DIRECTORY_SEPARATOR
            . 'theme-editor'
            . DIRECTORY_SEPARATOR
            . $area;

        $targetDir = $backupRoot . DIRECTORY_SEPARATOR . dirname($file);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create backup directory');
        }

        $backupPath = $backupRoot
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $file)
            . '.'
            . date('Ymd-His')
            . '.bak';

        if (!copy($path, $backupPath)) {
            throw new \RuntimeException('Unable to create template backup');
        }
    }

    private function restoreLatestBackup(string $area, string $file, string $path): void
    {
        $pattern = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'backups'
            . DIRECTORY_SEPARATOR
            . 'theme-editor'
            . DIRECTORY_SEPARATOR
            . $area
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $file)
            . '.*.bak';

        $backups = glob($pattern) ?: [];
        rsort($backups, SORT_STRING);

        $latest = $backups[0] ?? null;
        if ($latest === null || !is_file($latest)) {
            throw new \RuntimeException('No backup exists for this template');
        }

        if (!copy($latest, $path)) {
            throw new \RuntimeException('Unable to restore template backup');
        }
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