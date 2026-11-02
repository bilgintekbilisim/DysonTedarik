<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location', 30)->unique();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label');
            $table->string('type', 20)->default('url');
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name', 100)->nullable();
            $table->string('url')->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_mega')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });

        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('product_code', 100)->nullable();
            $table->string('quantity', 100)->nullable();
            $table->string('company_name')->nullable();
            $table->string('full_name');
            $table->string('phone', 30);
            $table->string('email');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('quote_requests');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
