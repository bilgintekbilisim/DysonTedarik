<?php

namespace Tests\Feature\Catalog;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\Catalog\ProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private ProductCatalog $catalog;

    private Category $root;

    private Category $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = app(ProductCatalog::class);
        $this->root = Category::factory()->create(['name' => 'Yapı Kimyasalları']);
        $this->child = Category::factory()->childOf($this->root)->create(['name' => 'Silikonlar']);
    }

    private function codes(?Category $category, array $filters = []): array
    {
        return $this->catalog->query($category, $filters)->pluck('code')->sort()->values()->all();
    }

    #[Test]
    public function category_listing_includes_sub_and_extra_categories_but_not_inactive_products(): void
    {
        Product::factory()->for($this->child)->create(['code' => 'A']);
        Product::factory()->for($this->root)->create(['code' => 'B']);
        Product::factory()->for(Category::factory())->create(['code' => 'C'])->categories()->attach($this->child);
        Product::factory()->for($this->child)->inactive()->create(['code' => 'D']);
        Product::factory()->for($this->child)->create(['code' => 'E', 'published_at' => now()->addDay()]);
        Product::factory()->for(Category::factory())->create(['code' => 'F']);

        $this->assertSame(['A', 'B', 'C'], $this->codes($this->root));
        $this->assertSame(['A', 'C'], $this->codes($this->child));
    }

    #[Test]
    public function brand_and_attribute_filters_combine(): void
    {
        $brandA = Brand::factory()->create(['name' => 'Marka A']);
        $brandB = Brand::factory()->create(['name' => 'Marka B']);
        $color = ProductAttribute::query()->create(['name' => 'Renk']);
        $white = AttributeValue::query()->create(['attribute_id' => $color->id, 'value' => 'Beyaz']);
        $grey = AttributeValue::query()->create(['attribute_id' => $color->id, 'value' => 'Gri']);

        Product::factory()->for($this->child)->for($brandA)->create(['code' => 'A1'])->attributeValues()->attach($white);
        Product::factory()->for($this->child)->for($brandA)->create(['code' => 'A2'])->attributeValues()->attach($grey);
        Product::factory()->for($this->child)->for($brandB)->create(['code' => 'B1'])->attributeValues()->attach($white);

        $this->assertSame(['A1', 'A2'], $this->codes($this->root, ['marka' => 'marka-a']));
        $this->assertSame(['A1', 'B1'], $this->codes($this->root, ['ozellik' => ['renk' => ['beyaz']]]));
        $this->assertSame(['A1', 'A2', 'B1'], $this->codes($this->root, ['ozellik' => ['renk' => 'beyaz,gri']]));
        $this->assertSame(['A1'], $this->codes($this->root, ['marka' => ['marka-a'], 'ozellik' => ['renk' => ['beyaz']]]));
    }

    #[Test]
    public function search_matches_name_code_and_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Soudal Örnek']);
        Product::factory()->create(['code' => 'X1', 'name' => 'Montaj Köpüğü']);
        Product::factory()->create(['code' => 'PU-500', 'name' => 'Ürün']);
        Product::factory()->for($brand)->create(['code' => 'S1']);

        $this->assertSame(['X1'], $this->codes(null, ['q' => 'köpüğü']));
        $this->assertSame(['PU-500'], $this->codes(null, ['q' => 'PU-5']));
        $this->assertSame(['S1'], $this->codes(null, ['q' => 'Soudal']));
        $this->assertSame([], $this->codes(null, ['q' => '%']), 'LIKE joker karakterleri kaçışlanmalı');
    }

    #[Test]
    public function malformed_filter_values_are_ignored(): void
    {
        Product::factory()->for($this->child)->create(['code' => 'A']);

        $this->assertSame(['A'], $this->codes($this->root, ['marka' => "x' OR 1=1 --", 'ozellik' => ['renk' => ['<script>']]]));
    }

    #[Test]
    public function facets_only_list_options_with_products_in_the_category(): void
    {
        $used = Brand::factory()->create();
        Brand::factory()->create(); // ürünü olmayan marka
        $color = ProductAttribute::query()->create(['name' => 'Renk']);
        $white = AttributeValue::query()->create(['attribute_id' => $color->id, 'value' => 'Beyaz']);
        AttributeValue::query()->create(['attribute_id' => $color->id, 'value' => 'Siyah']);
        ProductAttribute::query()->create(['name' => 'Kullanılmayan']);

        Product::factory()->for($this->child)->for($used)->create()->attributeValues()->attach($white);

        $facets = $this->catalog->facets($this->root);

        $this->assertSame([$used->id], $facets['brands']->pluck('id')->all());
        $this->assertSame(['Renk'], $facets['attributes']->pluck('name')->all());
        $this->assertSame(['Beyaz'], $facets['attributes']->first()->values->pluck('value')->all());
        $this->assertSame(1, $facets['attributes']->first()->values->first()->products_count);
        $this->assertSame([$this->child->id], $facets['children']->pluck('id')->all());
    }
}
