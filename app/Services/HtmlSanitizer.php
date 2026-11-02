<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Zengin metin editöründen gelen HTML'i beyaz liste ile temizler.
 * script, style, iframe, olay öznitelikleri (onclick vb.) ve javascript: bağlantıları kaldırılır.
 */
class HtmlSanitizer
{
    private SymfonySanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            // Filament RichEditor ekli görselleri data-id ile tanır; kaldırılırsa düzenlemede görsel kaybolur.
            ->allowAttribute('data-id', 'img')
            ->dropElement('style')
            ->withMaxInputLength(500_000);

        $this->sanitizer = new SymfonySanitizer($config);
    }

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        return $this->sanitizer->sanitize($html);
    }

    /**
     * Blok verisi içinde belirtilen zengin metin alanlarını temizler.
     *
     * @param  array<int, array{type: string, data: array}>|null  $blocks
     * @param  array<int, string>  $richFields
     */
    public function cleanBlocks(?array $blocks, array $richFields = ['content', 'body']): ?array
    {
        if ($blocks === null) {
            return null;
        }

        return array_map(function (array $block) use ($richFields) {
            foreach ($richFields as $field) {
                if (isset($block['data'][$field]) && is_string($block['data'][$field])) {
                    $block['data'][$field] = $this->clean($block['data'][$field]);
                }
            }

            return $block;
        }, array_values($blocks));
    }
}
