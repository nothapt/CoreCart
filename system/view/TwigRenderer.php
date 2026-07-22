<?php
declare(strict_types=1);

namespace CoreCart\System\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRenderer implements TemplateRendererInterface
{
    private Environment $twig;

    public function __construct(ThemeResolver $theme, AssetResolver $assets, bool $debug = false)
    {
        $loader = new FilesystemLoader();
        $loader->addPath($theme->getTemplateDir(), $theme->getTwigNamespace());

        $this->twig = new Environment($loader, [
            'autoescape'       => 'html',
            'strict_variables' => $debug,
            'cache'            => dirname(__DIR__, 2) . '/storage/cache/twig',
            'debug'            => $debug,
        ]);

        if ($debug) {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        $this->twig->addGlobal('assets', $assets);
        $this->twig->addGlobal('theme', $theme);
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }

    public function addGlobal(string $name, mixed $value): void
    {
        $this->twig->addGlobal($name, $value);
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }
}
