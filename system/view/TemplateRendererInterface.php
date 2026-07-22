<?php
declare(strict_types=1);

namespace CoreCart\System\View;

interface TemplateRendererInterface
{
    /**
     * Render a template and return the HTML content.
     *
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string;
}
