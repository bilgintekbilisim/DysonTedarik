<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ürün serisinin ebat / artikel / ambalaj tablosu: {"headers": [...], "rows": [[...], ...]} */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('variant_table')->nullable()->after('technical_specs');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variant_table');
        });
    }
};
