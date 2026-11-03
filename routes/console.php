<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
| Zamanlanmış görevler. cPanel'de tek bir cron satırı yeterlidir:
|   * * * * * /opt/cpanel/ea-php83/root/usr/bin/php /home/KULLANICI/apps/dysontedarik/artisan schedule:run >> /dev/null 2>&1
|
| Görevler bilerek Schedule::call ile aynı süreçte çalışır. Schedule::command her görevi ayrı bir
| PHP süreci olarak başlatır ve bunun için proc_open kullanır; hostingde proc_open kapalıdır.
*/

// Cron'un çalıştığını site:check ile doğrulayabilmek için kalp atışı.
Schedule::call(fn () => Cache::forever('scheduler.heartbeat', now()->timestamp))
    ->name('scheduler-heartbeat')
    ->everyMinute();

// Günlük yedek (veritabanı + yüklenen dosyalar), son 14 yedek saklanır.
Schedule::call(fn () => Artisan::call('site:backup', ['--keep' => 14]))
    ->name('site-backup')
    ->dailyAt('03:30')
    ->withoutOverlapping(120);

// Süresi dolmuş şifre sıfırlama bağlantılarını temizle.
Schedule::call(fn () => Artisan::call('auth:clear-resets'))
    ->name('clear-password-resets')
    ->daily();
