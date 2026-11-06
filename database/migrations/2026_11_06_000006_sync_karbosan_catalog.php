<?php

use App\Models\Brand;
use Database\Seeders\KarbosanCatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Yayındaki sitede Karbosan kataloğunu tamamlar: yeni ürün gruplarını ve serileri ekler, seri görsellerini bağlar.
     * Yalnızca Karbosan kataloğu önceden yüklenmiş kurulumlarda çalışır; ilk kurulumda seeder bunu zaten yapar.
     */
    public function up(): void
    {
        if (Brand::withTrashed()->where('name', 'Karbosan')->exists()) {
            (new KarbosanCatalogSeeder)->run();
        }
    }

    public function down(): void
    {
        // Eklenen ürünler panelden yönetilir; geri alırken silinmez.
    }
};
