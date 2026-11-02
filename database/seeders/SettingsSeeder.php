<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Yalnızca yapısal varsayılanlar. Telefon, adres, e-posta gibi firma bilgileri
 * doğrulanmadan girilmez; yönetici Site Ayarları'ndan doldurur.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'general.site_name' => 'Dyson Tedarik',
            'seo.title_suffix' => ' | Dyson Tedarik',
            'footer.copyright' => '© {yil} Dyson Tedarik. Tüm hakları saklıdır.',
            'footer.show_categories' => true,
            'footer.category_limit' => 8,
            'footer.show_contact' => true,
            'footer.show_social' => true,
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        app(SettingsService::class)->flush();
    }
}
