<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use App\Services\SettingsService;
use Database\Seeders\DysonCatalogSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DysonCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_catalog_with_images_slides_and_logo_and_can_run_twice(): void
    {
        Storage::fake('public');
        $this->seed([PageSeeder::class, DysonCatalogSeeder::class]);

        $this->assertSame(18, Product::query()->count());
        $this->assertSame(4, Category::query()->roots()->count());
        $this->assertSame(1, Brand::query()->count());
        $this->assertSame(3, Slide::query()->count());
        $this->assertTrue(Product::query()->doesntHave('images')->doesntExist(), 'Her ürünün görseli olmalı');
        Storage::disk('public')->assertExists(Product::query()->where('code', 'DYSN2594')->firstOrFail()->images->first()->path);
        Storage::disk('public')->assertExists(app(SettingsService::class)->get('general.logo'));

        foreach (Slide::all() as $slide) {
            Storage::disk('public')->assertExists([$slide->image_desktop, $slide->image_mobile]);
            $this->assertStringStartsWith('/kategori/', $slide->button_url);
        }

        // Panelde değiştirilen ürün ikinci çalıştırmada ezilmez, kopya oluşmaz.
        Product::query()->where('code', 'DP-MLT-200')->update(['name' => 'Panelde değişti']);
        $this->seed(DysonCatalogSeeder::class);

        $this->assertSame(18, Product::query()->count());
        $this->assertSame(3, Slide::query()->count());
        $this->assertSame('Panelde değişti', Product::query()->where('code', 'DP-MLT-200')->value('name'));
    }
}
