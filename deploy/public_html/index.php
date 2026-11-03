<?php

/*
| Web klasörü (public_html) proje dışında olduğunda kullanılan giriş dosyası.
| deploy.sh bu şablonu PUBLIC_PATH klasörüne kopyalar ve __APP_DIR__ yerine projenin
| mutlak yolunu yazar. Proje kodu, .env ve yedekler web'den erişilemeyen klasörde kalır.
|
|   /home/KULLANICI/apps/dysontedarik   ← proje (APP_DIR)
|   /home/KULLANICI/public_html         ← bu dosya, .htaccess, build/, js|css|fonts/filament, storage/
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$project = '__APP_DIR__';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $project.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $project.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $project.'/bootstrap/app.php';

// .env'deki PUBLIC_PATH de aynı klasörü gösterir; config önbelleği yokken de doğru çalışsın.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
