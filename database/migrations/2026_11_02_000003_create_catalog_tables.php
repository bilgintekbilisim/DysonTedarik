<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $this->seoColumns($table);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->longText('description')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $this->seoColumns($table);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 100)->unique();
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('usage_areas')->nullable();
            $table->json('technical_specs')->nullable();
            $table->string('color')->nullable();
            $table->string('volume')->nullable();
            $table->string('packaging')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $this->seoColumns($table);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
            $table->index('name');
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit', 30)->nullable();
            $table->boolean('is_filterable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
        });

        Schema::create('attribute_value_product', function (Blueprint $table) {
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['attribute_value_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('product_documents');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }

    private function seoColumns(Blueprint $table): void
    {
        $table->string('meta_title')->nullable();
        $table->string('meta_description', 500)->nullable();
        $table->string('og_image')->nullable();
        $table->string('canonical_url')->nullable();
        $table->boolean('noindex')->default(false);
    }
};
