<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Dyson Tedarik cari bilgileri (firma tarafından iletilen cari bilgi formundan).
 * Yalnızca boş olan ayarları doldurur; panelden yapılan değişiklikler ezilmez.
 */
class CompanyInfoSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'general.company_title' => 'DYSON TEDARİK YAPI VE SAN. ÜRÜNLERİ TİC. A.Ş.',

            'contact.phone' => '+90 530 422 17 28',
            'contact.phone_label' => 'Yetkili',
            'contact.phone_secondary' => '+90 531 835 34 19',
            'contact.phone_secondary_label' => 'Muhasebe',
            'contact.whatsapp' => '+90 530 422 17 28',
            'contact.email' => 'info@dysontedarik.com',
            'contact.email_secondary' => 'muhasebe@dysontedarik.com',
            'contact.email_secondary_label' => 'Muhasebe',
            'contact.address' => 'Ferhatpaşa Mah. 77 Sok. No: 24/1',
            'contact.district' => 'Ataşehir',
            'contact.city' => 'İstanbul',
            'contact.postal_code' => '34888',

            'company.tax_office' => 'Küçükyalı',
            'company.tax_number' => '7331305347',
            // Formda 15 hane görünüyordu (Excel baştaki 0'ı siler); MERSİS 16 hanedir: 0 + vergi no + 00001.
            'company.mersis' => '0733130534700001',
            'company.trade_registry' => '110395',
            'company.website' => 'https://www.dysontedarik.com',
            'company.show_in_footer' => true,
        ];

        foreach ($values as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        app(SettingsService::class)->flush();
    }
}
