<?php

namespace App\Support\Blocks;

/**
 * CMS blok türlerinin tek kaynağı. Ön yüz yalnızca burada tanımlı türleri,
 * resources/views/blocks/{tür}.blade.php şablonuyla çizer.
 */
class BlockRegistry
{
    /** @var array<string, array{label: string, icon: string, variants: array<string, string>, home_only: bool}> */
    public const BLOCKS = [
        'hero_slider' => ['label' => 'Hero slider', 'icon' => 'heroicon-o-photo', 'variants' => ['full' => 'Tam genişlik', 'contained' => 'Kutulu'], 'home_only' => true],
        'category_cards' => ['label' => 'Kategori kartları', 'icon' => 'heroicon-o-squares-2x2', 'variants' => ['grid' => 'Izgara', 'carousel' => 'Kaydırmalı'], 'home_only' => true],
        'featured_products' => ['label' => 'Öne çıkan ürünler', 'icon' => 'heroicon-o-star', 'variants' => ['grid' => 'Izgara', 'carousel' => 'Kaydırmalı'], 'home_only' => true],
        'latest_products' => ['label' => 'Yeni eklenen ürünler', 'icon' => 'heroicon-o-sparkles', 'variants' => ['grid' => 'Izgara', 'carousel' => 'Kaydırmalı'], 'home_only' => true],
        'featured_brands' => ['label' => 'Öne çıkan markalar', 'icon' => 'heroicon-o-tag', 'variants' => ['logos' => 'Logo şeridi', 'cards' => 'Kartlar'], 'home_only' => true],
        'about_intro' => ['label' => 'Firma tanıtım alanı', 'icon' => 'heroicon-o-building-office-2', 'variants' => ['image_left' => 'Görsel solda', 'image_right' => 'Görsel sağda'], 'home_only' => false],
        'features' => ['label' => 'İkonlu özellik listesi (Neden Biz? / Kalite ve güven)', 'icon' => 'heroicon-o-check-badge', 'variants' => ['cards' => 'Kartlar', 'list' => 'Liste'], 'home_only' => false],
        'stats' => ['label' => 'İstatistikler', 'icon' => 'heroicon-o-chart-bar', 'variants' => ['dark' => 'Koyu zemin', 'light' => 'Açık zemin'], 'home_only' => false],
        'promo_banners' => ['label' => 'Ürün tanıtım bannerları', 'icon' => 'heroicon-o-rectangle-group', 'variants' => ['two_column' => 'İki sütun', 'full' => 'Tek sütun'], 'home_only' => true],
        'whatsapp_cta' => ['label' => 'WhatsApp iletişim alanı', 'icon' => 'heroicon-o-chat-bubble-left-right', 'variants' => ['default' => 'Standart'], 'home_only' => false],
        'contact_cta' => ['label' => 'İletişim çağrısı', 'icon' => 'heroicon-o-phone', 'variants' => ['dark' => 'Lacivert', 'accent' => 'Turuncu'], 'home_only' => false],
        'rich_text' => ['label' => 'Zengin metin', 'icon' => 'heroicon-o-document-text', 'variants' => ['narrow' => 'Dar', 'wide' => 'Geniş'], 'home_only' => false],
        'text_image' => ['label' => 'Metin ve görsel', 'icon' => 'heroicon-o-photo', 'variants' => ['image_left' => 'Görsel solda', 'image_right' => 'Görsel sağda'], 'home_only' => false],
        'image' => ['label' => 'Görsel', 'icon' => 'heroicon-o-camera', 'variants' => ['contained' => 'Kutulu', 'full' => 'Tam genişlik'], 'home_only' => false],
    ];

    /** Özellik listelerinde seçilebilen ikonlar (Heroicons outline): değer => etiket. */
    public const ICONS = [
        'shield-check' => 'Güvence',
        'check-badge' => 'Kalite',
        'truck' => 'Teslimat',
        'cube' => 'Ürün / stok',
        'wrench-screwdriver' => 'Teknik destek',
        'clock' => 'Hız / zaman',
        'chat-bubble-left-right' => 'İletişim',
        'building-storefront' => 'Tedarik',
        'users' => 'Ekip',
        'document-check' => 'Doküman',
        'sparkles' => 'Yenilik',
        'hand-thumb-up' => 'Memnuniyet',
    ];

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::BLOCKS);
    }

    /** @return array<int, string> */
    public static function types(bool $includeHomeOnly = true): array
    {
        return array_keys(array_filter(
            self::BLOCKS,
            fn (array $block) => $includeHomeOnly || ! $block['home_only'],
        ));
    }

    public static function view(string $type): string
    {
        return 'blocks.'.str_replace('_', '-', $type);
    }

    /**
     * Ön yüzde gösterilecek blokları süzer: bilinmeyen tür ve pasif bloklar atlanır.
     *
     * @return array<int, array{type: string, data: array}>
     */
    public static function renderable(?array $blocks): array
    {
        return array_values(array_filter(
            $blocks ?? [],
            fn ($block) => is_array($block)
                && self::exists($block['type'] ?? '')
                && ($block['data']['is_active'] ?? true),
        ));
    }
}
