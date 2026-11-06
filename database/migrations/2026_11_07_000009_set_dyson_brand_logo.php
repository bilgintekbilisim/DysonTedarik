<?php

use App\Models\Brand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /** Dyson Professional markasının logosu boşsa firma logosunu ata (Markalar sayfası ve filtrelerde görünür). */
    public function up(): void
    {
        $brand = Brand::query()->where('name', 'like', 'Dyson%')->whereNull('logo')->first();
        $asset = database_path('seeders/assets/dyson/logo.png');

        if (! $brand || ! is_file($asset)) {
            return;
        }

        Storage::disk('public')->put('brands/dyson-professional-logo.png', file_get_contents($asset));
        $brand->update(['logo' => 'brands/dyson-professional-logo.png']);
    }

    public function down(): void {}
};
