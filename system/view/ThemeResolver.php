<?php
declare(strict_types=1);

namespace CoreCart\System\View;

class ThemeResolver
{
    private string $area;
    private string $theme;
    private string $rootDir;

    public function __construct(string $area, string $theme = 'default', ?string $rootDir = null)
    {
        if (!in_array($area, ['catalog', 'admin'], true)) {
            throw new \InvalidArgumentException("Unsupported theme area: {$area}");
        }

        if (!preg_match('/^[a-z0-9_-]+$/i', $theme)) {
            throw new \InvalidArgumentException("Invalid theme name: {$theme}");
        }

        $this->area = $area;
        $this->theme = $theme;
        $this->rootDir = $rootDir ?? dirname(__DIR__, 2);
    }

    public function getTemplateDir(): string
    {
        return $this->rootDir . '/' . $this->area . '/View/theme/' . $this->theme . '/template';
    }

    public function getAssetsDir(): string
    {
        return $this->rootDir . '/' . $this->area . '/View/theme/' . $this->theme . '/assets';
    }

    public function getAssetsUrl(): string
    {
        return '/' . $this->area . '/View/theme/' . $this->theme . '/assets';
    }

    public function getTwigNamespace(): string
    {
        return match ($this->area) {
            'catalog' => 'storefront',
            'admin'   => 'admin',
            default   => throw new \InvalidArgumentException("Unsupported theme area: {$this->area}"),
        };
    }

    public function getArea(): string
    {
        return $this->area;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }
}
