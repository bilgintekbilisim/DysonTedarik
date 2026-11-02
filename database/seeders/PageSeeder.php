<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Başlangıç sayfaları içeriksiz ve taslak olarak oluşturulur; metinler yönetici tarafından girilip yayınlanır.
 * Ana sayfa, önerilen blok sırasıyla gelir; firma bilgisi gerektiren bloklar içerik girilene kadar pasiftir.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['Hakkımızda', 'hakkimizda', PageType::Standard, true],
            ['Vizyonumuz', 'vizyonumuz', PageType::Standard, true],
            ['Misyonumuz', 'misyonumuz', PageType::Standard, true],
            ['Kalite Politikamız', 'kalite-politikamiz', PageType::Standard, true],
            ['Değerlerimiz', 'degerlerimiz', PageType::Standard, true],
            ['Neden Dyson Tedarik?', 'neden-dyson-tedarik', PageType::Standard, true],
            ['İletişim', 'iletisim', PageType::Contact, false],
            ['Gizlilik Politikası', 'gizlilik-politikasi', PageType::Legal, false],
            ['KVKK Aydınlatma Metni', 'kvkk-aydinlatma-metni', PageType::Legal, false],
            ['Çerez Politikası', 'cerez-politikasi', PageType::Legal, false],
        ];

        foreach ($pages as $index => [$title, $slug, $type, $inMenu]) {
            Page::withTrashed()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'type' => $type,
                'status' => ContentStatus::Draft,
                'show_in_menu' => $inMenu,
                'sort_order' => $index + 1,
            ]);
        }

        $homeBlocks = $this->homeBlocks();

        Page::withTrashed()->firstOrCreate(['type' => PageType::Home], [
            'title' => 'Ana Sayfa',
            'slug' => 'ana-sayfa',
            'status' => ContentStatus::Published,
            'blocks' => $homeBlocks,
            'draft_blocks' => $homeBlocks,
        ]);
    }

    private function homeBlocks(): array
    {
        $block = fn (string $type, array $data, bool $active = true, ?string $variant = null) => [
            'type' => $type,
            'data' => ['is_active' => $active, 'visibility' => 'all', 'variant' => $variant, 'anchor' => null, ...$data],
        ];

        return [
            $block('hero_slider', ['title' => 'Ana slider', 'autoplay' => true], variant: 'full'),
            $block('category_cards', ['title' => 'Ürün Kategorileri', 'subtitle' => null, 'category_ids' => [], 'limit' => 8], variant: 'grid'),
            $block('featured_products', ['title' => 'Öne Çıkan Ürünler', 'subtitle' => null, 'limit' => 8, 'button_text' => 'Tüm ürünler', 'button_url' => '/urunler'], variant: 'grid'),
            $block('about_intro', ['eyebrow' => 'Hakkımızda', 'title' => null, 'content' => null, 'image' => null, 'image_alt' => null, 'button_text' => 'Daha fazla', 'button_url' => '/hakkimizda'], active: false, variant: 'image_right'),
            $block('features', ['title' => 'Neden Biz?', 'subtitle' => null, 'items' => []], active: false, variant: 'cards'),
            $block('latest_products', ['title' => 'Yeni Eklenen Ürünler', 'subtitle' => null, 'limit' => 8, 'button_text' => null, 'button_url' => null], variant: 'carousel'),
            $block('promo_banners', ['title' => null, 'limit' => 2], variant: 'two_column'),
            $block('featured_brands', ['title' => 'Markalarımız', 'subtitle' => null, 'limit' => 12], variant: 'logos'),
            $block('stats', ['title' => null, 'items' => []], active: false, variant: 'dark'),
            $block('whatsapp_cta', ['title' => 'Ürün hakkında hızlı bilgi alın', 'text' => null, 'button_text' => 'WhatsApp ile yazın', 'prefill' => null], variant: 'default'),
            $block('contact_cta', ['title' => 'Projeniz için teklif alın', 'text' => null, 'button_text' => 'Teklif iste', 'button_url' => '/teklif-iste', 'show_phone' => true], variant: 'dark'),
        ];
    }
}
