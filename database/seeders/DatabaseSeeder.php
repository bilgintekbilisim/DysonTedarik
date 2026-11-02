<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model olayları bilerek açık bırakılır: slug üretimi ve HTML temizliği olaylarla çalışır.
     * Tüm seeder'lar tekrar çalıştırılabilir (mevcut kayıtları ezmez).
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingsSeeder::class,
            CompanyInfoSeeder::class,
            PageSeeder::class,
            MenuSeeder::class,
            DysonCatalogSeeder::class,
            CorporateContentSeeder::class,
        ]);
    }
}
