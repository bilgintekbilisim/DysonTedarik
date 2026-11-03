#!/usr/bin/env bash
#
# Dyson Tedarik — sunucuda kurulum ve güncelleme betiği (cPanel, SSH).
#
#   İlk kurulum:   bash deploy.sh --ilk-kurulum [paket.zip]
#   Güncelleme:    bash deploy.sh [paket.zip]
#
# paket.zip, yerelde scripts/paket-olustur.ps1 ile üretilen yayın paketidir. Verilirse önce
# veritabanı yedeği alınır, sonra paket proje klasörüne açılır. Ayrıntılar: docs/DEPLOY.md
#
# PHP / Composer yolu hostingde farklıysa:  PHP_BIN=/usr/local/bin/ea-php83 bash deploy.sh

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"
FIRST_INSTALL=0
PACKAGE=""

for arg in "$@"; do
    case "$arg" in
        --ilk-kurulum) FIRST_INSTALL=1 ;;
        *.zip) PACKAGE="$arg" ;;
        *) echo "Bilinmeyen parametre: $arg" >&2; exit 2 ;;
    esac
done

cd "$APP_DIR"
step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

# Composer sunucuda yoksa proje içindeki composer.phar kullanılır.
if ! command -v "$COMPOSER" >/dev/null 2>&1; then
    if [ -f composer.phar ]; then COMPOSER="$PHP composer.phar"; else
        echo "Composer bulunamadı. COMPOSER_BIN=... verin veya composer.phar'ı proje köküne koyun." >&2; exit 1
    fi
fi

"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || {
    echo "PHP 8.3+ gerekli; '$PHP' sürümü: $("$PHP" -r 'echo PHP_VERSION;')" >&2; exit 1; }

if [ ! -f .env ]; then
    echo ".env bulunamadı. .env.production.example dosyasını .env olarak kopyalayıp doldurun." >&2
    exit 1
fi

if [ "$FIRST_INSTALL" -eq 0 ] && [ -f vendor/autoload.php ]; then
    step "Bakım modu açılıyor"
    "$PHP" artisan down --retry=60 || true
    # Hata olursa site bakım modunda kalır; sorun giderilince: php artisan up
fi

if [ -n "$PACKAGE" ]; then
    if [ -f vendor/autoload.php ]; then
        step "Güncelleme öncesi veritabanı yedeği"
        "$PHP" artisan site:backup --no-files || echo "Uyarı: yedek alınamadı, devam ediliyor."
    fi
    step "Paket açılıyor: $PACKAGE"
    unzip -oq "$PACKAGE" -d "$APP_DIR"
fi

step "Composer bağımlılıkları (production)"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

if [ "$FIRST_INSTALL" -eq 1 ] && ! grep -qE '^APP_KEY=.+' .env; then
    step "Uygulama anahtarı üretiliyor"
    "$PHP" artisan key:generate --force
fi

step "Veritabanı tabloları"
"$PHP" artisan migrate --force

if [ "$FIRST_INSTALL" -eq 1 ]; then
    step "İlk içerik yükleniyor (yönetici, ayarlar, sayfalar, menüler, kataloglar)"
    "$PHP" artisan db:seed --force
fi

step "Yükleme klasörü"
PUBLIC_DISK_ROOT="$(grep -E '^PUBLIC_DISK_ROOT=' .env | cut -d= -f2- | tr -d '"' || true)"
if [ -n "$PUBLIC_DISK_ROOT" ]; then
    # Sembolik bağlantı kullanılmıyor: yüklemeler doğrudan bu gerçek klasöre yazılır.
    mkdir -p "$PUBLIC_DISK_ROOT"
    cp -n storage/app/public/.htaccess "$PUBLIC_DISK_ROOT/.htaccess" 2>/dev/null || true
    echo "PUBLIC_DISK_ROOT kullanılıyor: $PUBLIC_DISK_ROOT"
elif [ ! -e public/storage ]; then
    "$PHP" artisan storage:link || echo "Uyarı: sembolik bağlantı kurulamadı. docs/DEPLOY.md → 'storage:link çalışmıyorsa'."
fi

step "Önbellekler"
"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize

step "Dosya izinleri"
find storage bootstrap/cache -type d -exec chmod 755 {} +
find storage bootstrap/cache -type f -exec chmod 644 {} +

if [ "$FIRST_INSTALL" -eq 0 ]; then
    step "Bakım modu kapatılıyor"
    "$PHP" artisan up
fi

step "Kurulum kontrolü"
"$PHP" artisan site:check || true

if [ "$FIRST_INSTALL" -eq 1 ]; then
    printf '\n\033[1;33mİlk kurulum bitti. Şimdi .env içindeki ADMIN_EMAIL / ADMIN_PASSWORD satırlarını silin ve php artisan config:cache çalıştırın.\033[0m\n'
fi
