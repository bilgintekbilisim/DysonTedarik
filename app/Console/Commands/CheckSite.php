<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

/**
 * Yayın sonrası kontrol listesi: ortam, güvenlik, veritabanı, dosya izinleri, derlenmiş dosyalar,
 * e-posta, cron ve yedek. Kritik bir sorun varsa 1 ile çıkar.
 */
#[Signature('site:check')]
#[Description('Production kurulumunu kontrol eder (debug, https, depolama, cron, yedek, e-posta)')]
class CheckSite extends Command
{
    private int $failures = 0;

    public function handle(): int
    {
        $production = app()->isProduction();

        $this->line('<options=bold>Ortam</>');
        $this->check('PHP sürümü 8.3+', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION);
        $missing = array_filter(['pdo_mysql', 'mbstring', 'intl', 'gd', 'zip', 'fileinfo', 'openssl', 'curl'], fn ($ext) => ! extension_loaded($ext));
        $this->check('Gerekli PHP eklentileri', $missing === [], $missing ? 'eksik: '.implode(', ', $missing) : 'tamam');
        $this->check('APP_ENV=production', $production, app()->environment(), critical: false);
        $this->check('APP_DEBUG kapalı', ! config('app.debug'), config('app.debug') ? 'AÇIK — hata ayrıntıları ziyaretçiye görünür!' : 'kapalı', critical: $production);
        $this->check('APP_KEY tanımlı', filled(config('app.key')));
        $this->check('APP_URL https', str_starts_with((string) config('app.url'), 'https://'), config('app.url'), critical: $production);
        $this->check('Oturum çerezi yalnızca https', (bool) config('session.secure') || ! $production, config('session.secure') ? 'evet' : 'hayır (SESSION_SECURE_COOKIE=true)', critical: $production);

        $this->line('');
        $this->line('<options=bold>Uygulama</>');
        try {
            DB::connection()->getPdo();
            $this->check('Veritabanı bağlantısı', true, DB::connection()->getDatabaseName());
            $this->check('Aktif yönetici hesabı', User::query()->where('role', UserRole::Admin)->where('is_active', true)->exists());
        } catch (Throwable $e) {
            $this->check('Veritabanı bağlantısı', false, $e->getMessage());
        }

        $uploads = Storage::disk('public')->path('');
        $this->check('Yükleme klasörü web\'de erişilebilir', File::exists(public_path('storage')), File::exists(public_path('storage'))
            ? (is_link(public_path('storage')) ? 'public/storage → bağlantı' : 'public/storage gerçek klasör')
            : 'php artisan storage:link (veya PUBLIC_DISK_ROOT)');
        $this->check('Yükleme klasörü yazılabilir', is_writable($uploads), $uploads);
        $this->check('storage yazılabilir', is_writable(storage_path('framework')) && is_writable(storage_path('logs')));
        $this->check('bootstrap/cache yazılabilir', is_writable(base_path('bootstrap/cache')));
        $this->check('Derlenmiş ön yüz dosyaları', File::exists(public_path('build/manifest.json')), 'public/build/manifest.json');
        $this->check('Filament dosyaları', File::isDirectory(public_path('js/filament')), 'php artisan filament:assets');
        $this->check('Yükleme klasöründe PHP kapalı', File::exists($uploads.'.htaccess'), $uploads.'.htaccess');
        $this->check('Config önbelleği', app()->configurationIsCached(), app()->configurationIsCached() ? 'açık' : 'php artisan optimize', critical: false);

        $this->line('');
        $this->line('<options=bold>E-posta, cron ve yedek</>');
        $mailer = config('mail.default');
        $this->check('Gerçek e-posta gönderimi', ! in_array($mailer, ['log', 'array'], true), "MAIL_MAILER={$mailer}", critical: $production);

        $beat = Cache::get('scheduler.heartbeat');
        $this->check('Cron (schedule:run) çalışıyor', $beat && now()->timestamp - $beat < 300, $beat ? 'son: '.now()->setTimestamp($beat)->diffForHumans() : 'hiç çalışmamış', critical: false);

        $last = collect(File::glob(storage_path('app/private/backups/yedek-*.zip')))->sort()->last();
        $age = $last ? now()->diffInHours(now()->setTimestamp(filemtime($last))) : null;
        $this->check('Son 48 saatte yedek', $last && abs($age) < 48, $last ? basename($last) : 'yedek yok (php artisan site:backup)', critical: false);

        $this->line('');
        $this->failures
            ? $this->error("{$this->failures} kritik sorun var.")
            : $this->info('Kritik sorun bulunmadı.');

        return $this->failures ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $label, bool $ok, ?string $detail = null, bool $critical = true): void
    {
        $icon = $ok ? '<fg=green>✔</>' : ($critical ? '<fg=red>✘</>' : '<fg=yellow>!</>');
        $detail = $detail ? OutputFormatter::escape($detail) : null;
        $this->line('  '.$icon.' '.mb_str_pad($label, 34).' '.($detail ? "<fg=gray>{$detail}</>" : ''));

        if (! $ok && $critical) {
            $this->failures++;
        }
    }
}
