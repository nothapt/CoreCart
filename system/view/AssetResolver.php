<?php
declare(strict_types=1);

namespace CoreCart\System\View;

class AssetResolver
{
    private ThemeResolver $theme;
    private string $baseUrl;

    public function __construct(ThemeResolver $theme, string $baseUrl = '')
    {
        $this->theme = $theme;
        $this->baseUrl = $baseUrl;
    }

    public function url(string $path): string
    {
        return $this->baseUrl . $this->theme->getAssetsUrl() . '/' . ltrim($path, '/');
    }

    public function css(string $file): string
    {
        return $this->url('css/' . $file);
    }

    public function js(string $file): string
    {
        return $this->url('js/' . $file);
    }

    public function image(string $file): string
    {
        return $this->url('images/' . $file);
    }
}
