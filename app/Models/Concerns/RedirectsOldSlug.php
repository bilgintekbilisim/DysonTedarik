<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use Illuminate\Support\Facades\Route;

/**
 * Yayındaki bir kaydın slug'ı değişince eski adresten yenisine otomatik 301 oluşturur;
 * böylece arama motorlarındaki ve paylaşılmış eski bağlantılar kırılmaz.
 * Modeller publicRouteName() ile ön yüz rotasını bildirir.
 */
trait RedirectsOldSlug
{
    abstract public function publicRouteName(): ?string;

    public static function bootRedirectsOldSlug(): void
    {
        static::updated(function ($model) {
            $route = $model->publicRouteName();

            if (! $model->wasChanged('slug') || ! $route || ! Route::has($route) || blank($model->getOriginal('slug'))) {
                return;
            }

            $old = Redirect::normalizePath(route($route, $model->getOriginal('slug'), false));
            $new = Redirect::normalizePath(route($route, $model->slug, false));

            // Yeni adres artık canlı: ona işaret eden eski bir kural varsa kaldır (döngü olmasın).
            Redirect::query()->where('from_path', $new)->delete();

            // Eski adrese giden zincirleri doğrudan yeni adrese bağla.
            Redirect::query()->where('to_url', $old)->update(['to_url' => $new]);

            Redirect::query()->updateOrCreate(
                ['from_path' => $old],
                ['to_url' => $new, 'status_code' => 301, 'is_active' => true],
            );
        });
    }
}
