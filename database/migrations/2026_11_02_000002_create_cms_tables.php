<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('standard');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->string('cover_image')->nullable();
            $table->longText('content')->nullable();
            $table->json('blocks')->nullable();
            $table->json('draft_blocks')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('show_in_menu')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $this->seoColumns($table);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
        });

        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20)->default('hero');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_desktop');
            $table->string('image_mobile')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['group', 'is_active', 'sort_order']);
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 20)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('alt')->nullable();
            $table->string('folder', 50)->default('genel');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('folder');
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('media');
        Schema::dropIfExists('slides');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
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
