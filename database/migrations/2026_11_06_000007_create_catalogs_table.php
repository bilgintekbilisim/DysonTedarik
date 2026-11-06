<?php

use App\Enums\MenuItemType;
use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\Product;
use Database\Seeders\ECatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** E-Katalog: PDF ürün katalogları ve broşürler. */
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('file');
            $table->unsignedInteger('file_size')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Yayındaki kurulum: menüye "E-Katalog" ekle ve örnek kataloğu yükle. İlk kurulumda bunu seeder'lar yapar.
        if (! Product::query()->exists()) {
            return;
        }

        foreach ([MenuLocation::Header, MenuLocation::Mobile, MenuLocation::FooterQuick] as $location) {
            $menu = Menu::query()->where('location', $location)->first();
            if (! $menu || $menu->items()->where('route_name', 'catalogs.index')->exists()) {
                continue;
            }
            $after = $menu->items()->whereNull('parent_id')->where('route_name', 'brands.index')->value('sort_order')
                ?? $menu->items()->whereNull('parent_id')->max('sort_order');
            $menu->items()->whereNull('parent_id')->where('sort_order', '>', $after)->increment('sort_order');
            $menu->items()->create(['label' => 'E-Katalog', 'type' => MenuItemType::Route, 'route_name' => 'catalogs.index', 'sort_order' => $after + 1]);
        }

        (new ECatalogSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogs');
    }
};
