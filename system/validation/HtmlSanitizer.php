<?php
declare(strict_types=1);

namespace CoreCart\System\Validation;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'b', 'i',
        'ul', 'ol', 'li',
        'h2', 'h3', 'h4',
        'a', 'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    private const ALLOWED_ATTRS = [
        'a' => ['href', 'title'],
    ];

    private const SCHEME_PATTERN = '/^(https?|mailto|tel):/i';

    public function sanitize(string $html): string
    {
        $html = strip_tags($html, '<' . implode('>', self::ALLOWED_TAGS) . '>');

        $html = preg_replace_callback(
            '/<(\w+)(\s[^>]*)?>/i',
            static function (array $matches): string {
                $tag = strtolower($matches[1]);
                $attrString = $matches[2] ?? '';
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    return '';
                }
                $allowed = self::ALLOWED_ATTRS[$tag] ?? [];
                if ($allowed === [] && $attrString === '') {
                    return "<{$tag}>";
                }
                $cleaned = self::cleanAttributes($tag, $attrString, $allowed);
                return "<{$tag}{$cleaned}>";
            },
            $html
        );

        $html = preg_replace(
            '/javascript\s*:/i',
            '',
            $html
        );

        return trim($html);
    }

    private static function cleanAttributes(string $tag, string $attrString, array $allowed): string
    {
        if ($allowed === []) {
            return '';
        }

        $result = '';
        if (preg_match_all('/(\w+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+)))?/i', $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attr = strtolower($match[1]);
                if (!in_array($attr, $allowed, true)) {
                    continue;
                }
                $value = $match[2] ?? $match[3] ?? $match[4] ?? '';
                if ($attr === 'href' && !preg_match(self::SCHEME_PATTERN, $value)) {
                    continue;
                }
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $result .= " {$attr}=\"{$value}\"";
            }
        }

        return $result;
    }
}
