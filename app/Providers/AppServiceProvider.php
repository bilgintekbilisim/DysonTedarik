<?php

namespace App\Providers;

use App\Services\HtmlSanitizer;
use App\Services\SettingsService;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(HtmlSanitizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Geliştirmede fillable dışı alan atamalarını sessizce yutmak yerine hata ver.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
    }
}
