<?php

/*
| YALNIZCA "Yöntem C" için (bkz. docs/DEPLOY.md): domain'in document root'u değiştirilemiyor ve
| public_html sembolik bağlantı yapılamıyorsa, public/ klasörünün içeriği public_html'e kopyalanır
| ve public_html/index.php bu dosyayla değiştirilir. Proje kodu public_html DIŞINDA kalır.
|
| Klasör yapısı:  /home/KULLANICI/dysontedarik   (proje)
|                 /home/KULLANICI/public_html    (bu dosya + public/ içeriği)
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$project = dirname(__DIR__).'/dysontedarik';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $project.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $project.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $project.'/bootstrap/app.php';

// Derlenmiş dosyalar (build/) ve yüklemeler (storage/) bu klasörde.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
