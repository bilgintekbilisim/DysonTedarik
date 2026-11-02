<?php

namespace App\Providers;

use App\Services\HtmlSanitizer;
use App\Services\SettingsService;
use App\View\Composers\SiteComposer;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // APP_URL https ise üretilen tüm bağlantılar https olsun (proxy arkasında http görünse bile).
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Geliştirmede fillable dışı alan atamalarını sessizce yutmak yerine hata ver.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Alt görünümler layout'tan önce çizildiği için ortak verilere ihtiyaç duyanlar da listelenir.
        View::composer(['layouts.app', 'contact', 'quote'], SiteComposer::class);

        Paginator::defaultView('components.pagination');

        // Teklif ve iletişim formları: IP başına dakikada 5, günde 30 gönderim.
        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(5)->by('forms-m:'.$request->ip()),
            Limit::perDay(30)->by('forms-d:'.$request->ip()),
        ]);

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
    }
}
