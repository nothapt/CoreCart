<?php
declare(strict_types=1);

namespace CoreCart\System\View;

use CoreCart\System\Validation\HtmlSanitizer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SanitizeExtension extends AbstractExtension
{
    private HtmlSanitizer $sanitizer;

    public function __construct(?HtmlSanitizer $sanitizer = null)
    {
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('sanitize', [$this, 'sanitize'], ['is_safe' => ['html']]),
        ];
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}
