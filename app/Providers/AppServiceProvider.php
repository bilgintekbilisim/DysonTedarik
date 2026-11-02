<?php

namespace App\Providers;

use App\Services\HtmlSanitizer;
use App\Services\SettingsService;
use App\View\Composers\SiteComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
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

        // Alt görünümler layout'tan önce çizildiği için ortak verilere ihtiyaç duyanlar da listelenir.
        View::composer(['layouts.app', 'contact'], SiteComposer::class);

        Paginator::defaultView('components.pagination');

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
    }
}
