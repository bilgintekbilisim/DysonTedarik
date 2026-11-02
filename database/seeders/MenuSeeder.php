<?php

namespace Database\Seeders;

use App\Enums\MenuItemType;
use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\Page;
use Illuminate\Database\Seeder;

/** Varsayılan menü iskeleti. Bir konumda menü zaten varsa öğelerine dokunulmaz. */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $corporate = ['hakkimizda', 'vizyonumuz', 'misyonumuz', 'kalite-politikamiz', 'degerlerimiz', 'neden-dyson-tedarik'];
        $legal = ['gizlilik-politikasi', 'kvkk-aydinlatma-metni', 'cerez-politikasi'];

        $main = fn () => [
            ['Ana Sayfa', MenuItemType::Route, 'home'],
            ['Ürünler', MenuItemType::Route, 'products.index', [], true],
            ['Markalar', MenuItemType::Route, 'brands.index'],
            ['Kurumsal', MenuItemType::Url, '#', array_map(fn ($slug) => [null, MenuItemType::Page, $slug], $corporate)],
            ['Teklif İste', MenuItemType::Route, 'quote.create'],
            ['İletişim', MenuItemType::Route, 'contact'],
        ];

        $this->menu(MenuLocation::Header, 'Üst Menü', $main());
        $this->menu(MenuLocation::Mobile, 'Mobil Menü', $main());
        $this->menu(MenuLocation::FooterCorporate, 'Footer — Kurumsal', array_map(fn ($slug) => [null, MenuItemType::Page, $slug], $corporate));
        $this->menu(MenuLocation::FooterQuick, 'Footer — Hızlı Bağlantılar', [
            ['Tüm Ürünler', MenuItemType::Route, 'products.index'],
            ['Markalar', MenuItemType::Route, 'brands.index'],
            ['Teklif İste', MenuItemType::Route, 'quote.create'],
            ['İletişim', MenuItemType::Route, 'contact'],
        ]);
        $this->menu(MenuLocation::FooterLegal, 'Footer — Yasal', array_map(fn ($slug) => [null, MenuItemType::Page, $slug], $legal));
    }

    /** @param  array<int, array{0: ?string, 1: MenuItemType, 2: string, 3?: array, 4?: bool}>  $items */
    private function menu(MenuLocation $location, string $name, array $items): void
    {
        $menu = Menu::query()->firstOrCreate(['location' => $location], ['name' => $name]);

        if ($menu->items()->exists()) {
            return;
        }

        $this->createItems($menu, $items);
    }

    private function createItems(Menu $menu, array $items, ?int $parentId = null): void
    {
        foreach ($items as $order => $item) {
            [$label, $type, $target] = $item;
            $page = $type === MenuItemType::Page ? Page::query()->where('slug', $target)->first() : null;

            $created = $menu->items()->create([
                'parent_id' => $parentId,
                'label' => $label ?? $page?->title ?? $target,
                'type' => $type,
                'page_id' => $page?->id,
                'route_name' => $type === MenuItemType::Route ? $target : null,
                'url' => $type === MenuItemType::Url ? $target : null,
                'is_mega' => $item[4] ?? false,
                'sort_order' => $order + 1,
            ]);

            if (! empty($item[3])) {
                $this->createItems($menu, $item[3], $created->id);
            }
        }
    }
}
