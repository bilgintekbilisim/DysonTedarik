<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * İlk yönetici hesabı. Bilgiler .env içindeki ADMIN_EMAIL / ADMIN_PASSWORD değerlerinden okunur;
 * kodda şifre tutulmaz. Hesap zaten varsa dokunulmaz.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('İlk yönetici için .env dosyasına ADMIN_EMAIL ve ADMIN_PASSWORD ekleyin.');
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Site Yöneticisi'),
                'password' => $password,
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );
    }
}
