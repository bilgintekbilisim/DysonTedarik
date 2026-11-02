<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizer;
    }

    #[Test]
    public function it_removes_scripts_event_handlers_and_javascript_links(): void
    {
        $dirty = '<h2 onclick="alert(1)">Başlık</h2><script>alert(1)</script>'
            .'<a href="javascript:alert(1)">kötü</a><img src="x" onerror="alert(1)"><style>body{}</style>'
            .'<iframe src="https://evil.test"></iframe>';

        $clean = $this->sanitizer->clean($dirty);

        $this->assertStringContainsString('<h2>Başlık</h2>', $clean);
        $this->assertStringNotContainsStringIgnoringCase('script', $clean);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $clean);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $clean);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $clean);
        $this->assertStringNotContainsStringIgnoringCase('iframe', $clean);
        $this->assertStringNotContainsStringIgnoringCase('<style', $clean);
    }

    #[Test]
    public function it_keeps_editor_formatting_and_safe_links(): void
    {
        $html = '<p><strong>Kalın</strong> <em>italik</em></p><ul><li>Madde</li></ul>'
            .'<table><tr><td>Hücre</td></tr></table><a href="/urunler">Ürünler</a><a href="tel:+905000000000">Ara</a>';

        $clean = $this->sanitizer->clean($html);

        $this->assertStringContainsString('<strong>Kalın</strong>', $clean);
        $this->assertStringContainsString('<li>Madde</li>', $clean);
        $this->assertStringContainsString('<td>Hücre</td>', $clean);
        $this->assertStringContainsString('href="/urunler"', $clean);
        // "+" öznitelik içinde &#43; olarak kodlanır; tarayıcı bunu "+" okur.
        $this->assertMatchesRegularExpression('/href="tel:(\+|&#43;)905000000000"/', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }

    #[Test]
    public function it_returns_null_for_empty_input(): void
    {
        $this->assertNull($this->sanitizer->clean(null));
        $this->assertNull($this->sanitizer->clean('   '));
    }

    #[Test]
    public function it_cleans_only_rich_fields_inside_blocks(): void
    {
        $blocks = [
            'uuid-1' => ['type' => 'rich_text', 'data' => ['title' => 'Başlık', 'content' => '<p>Metin</p><script>x</script>']],
        ];

        $clean = $this->sanitizer->cleanBlocks($blocks);

        $this->assertSame([0], array_keys($clean), 'Builder uuid anahtarları sıralı listeye çevrilmeli');
        $this->assertSame('Başlık', $clean[0]['data']['title']);
        $this->assertSame('<p>Metin</p>', $clean[0]['data']['content']);
    }
}
