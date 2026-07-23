<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Validation\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer();
    }

    public function testAllowsSafeTags(): void
    {
        $html = '<p>Hello <strong>world</strong></p>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsFormatting(): void
    {
        $html = '<p><em>italic</em> and <b>bold</b> and <u>underline</u></p>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsLists(): void
    {
        $html = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsHeadings(): void
    {
        $html = '<h2>Title</h2><h3>Subtitle</h3>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsLinks(): void
    {
        $html = '<a href="https://example.com">Link</a>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testStripsJavaScriptHref(): void
    {
        $html = '<a href="javascript:alert(1)">XSS</a>';
        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('javascript', $result);
    }

    public function testStripsScriptTags(): void
    {
        $html = '<p>Hello</p><script>alert("xss")</script><p>World</p>';
        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('script', $result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
    }

    public function testStripsOnEventHandlers(): void
    {
        $html = '<p onclick="alert(1)">Click me</p>';
        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function testStripsIframe(): void
    {
        $html = '<iframe src="evil.com"></iframe><p>Safe</p>';
        $result = $this->sanitizer->sanitize($html);
        $this->assertStringNotContainsString('iframe', $result);
        $this->assertStringContainsString('Safe', $result);
    }

    public function testAllowsTables(): void
    {
        $html = '<table><tr><td>Cell</td></tr></table>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsCode(): void
    {
        $html = '<pre><code>echo "hello";</code></pre>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testAllowsBlockquote(): void
    {
        $html = '<blockquote>Important quote</blockquote>';
        $this->assertEquals($html, $this->sanitizer->sanitize($html));
    }

    public function testEscapesHtmlInAttributes(): void
    {
        $html = '<a title="foo&quot;bar" href="https://example.com">Link</a>';
        $result = $this->sanitizer->sanitize($html);
        $this->assertStringContainsString('foo&amp;quot;bar', $result);
    }

    public function testEmptyInput(): void
    {
        $this->assertEquals('', $this->sanitizer->sanitize(''));
    }

    public function testPlainText(): void
    {
        $this->assertEquals('Hello World', $this->sanitizer->sanitize('Hello World'));
    }
}
