<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

/*
| Zamanlanmış görevler. cPanel'de tek bir cron satırı yeterlidir:
|   * * * * * /usr/local/bin/php /home/KULLANICI/dysontedarik/artisan schedule:run >> /dev/null 2>&1
*/

// Cron'un çalıştığını site:check ile doğrulayabilmek için kalp atışı.
Schedule::call(fn () => Cache::forever('scheduler.heartbeat', now()->timestamp))->name('scheduler-heartbeat')->everyMinute();

// Günlük yedek (veritabanı + yüklenen dosyalar), son 14 yedek saklanır.
Schedule::command('site:backup --keep=14')->dailyAt('03:30')->withoutOverlapping()->onOneServer();

// Süresi dolmuş oturum, önbellek kilidi ve hız sınırı kayıtlarını temizle.
Schedule::command('cache:prune-stale-tags')->daily();
Schedule::command('model:prune')->daily();
