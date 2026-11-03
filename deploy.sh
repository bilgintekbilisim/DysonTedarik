#!/usr/bin/env bash
#
# Dyson Tedarik — sunucuda kurulum ve güncelleme (cPanel Terminal veya SSH).
#
#   İlk kurulum:   bash deploy.sh --ilk-kurulum ~/releases/<paket>.zip
#   Güncelleme:    bash deploy.sh ~/releases/<paket>.zip
#
# Paket scripts/paket-olustur.ps1 ile üretilir ve vendor/ içerir: sunucuda Composer ÇALIŞTIRILMAZ
# (proc_open kapalı). Sembolik bağlantı kullanılmaz (symlink kapalı): web klasörü .env'deki
# PUBLIC_PATH, yüklemeler PUBLIC_DISK_ROOT klasörüdür. Ayrıntılar: docs/DEPLOY.md

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
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
fail() { printf '\n\033[1;31mHATA: %s\033[0m\n' "$1" >&2; exit 1; }

# --- PHP 8.3: cPanel Terminal'in varsayılan "php" komutu 8.2 olabilir --------------------------
if [ -z "${PHP_BIN:-}" ]; then
    for candidate in /opt/cpanel/ea-php83/root/usr/bin/php /usr/local/bin/ea-php83 php; do
        if command -v "$candidate" >/dev/null 2>&1; then PHP_BIN="$candidate"; break; fi
    done
fi
PHP="${PHP_BIN:?PHP bulunamadı; PHP_BIN=/yol/php verin}"
"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' \
    || fail "PHP 8.3+ gerekli; '$PHP' sürümü $("$PHP" -r 'echo PHP_VERSION;'). PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php verin."
echo "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

artisan() { "$PHP" artisan "$@"; }

# .env'den tek değer okur (tırnaksız). Yoksa boş döner.
env_get() { grep -E "^$1=" .env 2>/dev/null | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' || true; }

# --- Paketi aç: unzip yoksa PHP ZipArchive kullanılır --------------------------------------------
extract_package() {
    if command -v unzip >/dev/null 2>&1; then
        unzip -oq "$1" -d "$APP_DIR"
    else
        "$PHP" -r '$z = new ZipArchive; if ($z->open($argv[1]) !== true) { fwrite(STDERR, "Zip açılamadı\n"); exit(1); } $z->extractTo($argv[2]); $z->close();' "$1" "$APP_DIR"
    fi
}

# --- Web klasörüne eşitleme: index.php şablondan, .htaccess cPanel'in PHP bloğu korunarak ---------
sync_public() {
    local target="$1"
    mkdir -p "$target"

    rm -rf "$target/build"
    cp -R public/build "$target/build"
    [ -f public/favicon.ico ] && cp -f public/favicon.ico "$target/favicon.ico"

    sed "s#__APP_DIR__#${APP_DIR}#g" deploy/public_html/index.php > "$target/index.php.yeni"
    mv -f "$target/index.php.yeni" "$target/index.php"

    # cPanel MultiPHP Manager domain'in PHP sürümünü .htaccess'e bir blok olarak yazar; korunmazsa
    # site sunucunun varsayılan PHP'siyle (8.5) çalışır.
    local begin='# php -- BEGIN cPanel-generated handler, do not edit'
    local end='# php -- END cPanel-generated handler, do not edit'
    local handler=""
    if [ -f "$target/.htaccess" ]; then
        handler="$(sed -n "/^${begin}\$/,/^${end}\$/p" "$target/.htaccess")"
        [ -f "$target/.htaccess.yedek" ] || cp "$target/.htaccess" "$target/.htaccess.yedek"
    fi
    {
        cat public/.htaccess
        if [ -n "$handler" ]; then printf '\n%s\n' "$handler"; fi
    } > "$target/.htaccess.yeni"
    mv -f "$target/.htaccess.yeni" "$target/.htaccess"
    if [ -n "$handler" ]; then echo "cPanel PHP satırı korundu."; else echo "Uyarı: .htaccess'te cPanel PHP satırı yok; MultiPHP Manager'da domain için PHP 8.3 seçin."; fi
}

# --- Ön kontroller --------------------------------------------------------------------------------
[ -f .env ] || fail ".env yok. cp .env.production.example .env ile oluşturup doldurun (docs/DEPLOY.md §6)."

PUBLIC_PATH="$(env_get PUBLIC_PATH)"
PUBLIC_DISK_ROOT="$(env_get PUBLIC_DISK_ROOT)"
[ -n "$PUBLIC_DISK_ROOT" ] || fail "PUBLIC_DISK_ROOT boş. Symlink kapalı olduğu için yüklemeler web klasöründeki gerçek bir klasöre yazılmalı (ör. PUBLIC_PATH/storage)."
case "$PUBLIC_DISK_ROOT" in /*) ;; *) fail "PUBLIC_DISK_ROOT mutlak yol olmalı: $PUBLIC_DISK_ROOT" ;; esac
if [ -n "$PUBLIC_PATH" ]; then
    case "$PUBLIC_PATH" in /*) ;; *) fail "PUBLIC_PATH mutlak yol olmalı: $PUBLIC_PATH" ;; esac
    case "$PUBLIC_PATH/" in "$APP_DIR"/*) fail "PUBLIC_PATH proje klasörünün içinde olamaz." ;; esac
    case "$APP_DIR/" in "$PUBLIC_PATH"/*) fail "Proje klasörü web klasörünün (PUBLIC_PATH) içinde olamaz: .env ve kaynak kod indirilebilir olur." ;; esac
fi

LIVE=0
if [ -f vendor/autoload.php ] && [ "$FIRST_INSTALL" -eq 0 ]; then
    step "Bakım modu açılıyor"
    artisan down --retry=60 && LIVE=1 || true
fi
trap 'if [ "$LIVE" -eq 1 ]; then printf "\n\033[1;33mSite bakım modunda kaldı. Sorun giderilince: %s artisan up\033[0m\n" "$PHP" >&2; fi' ERR

if [ -n "$PACKAGE" ]; then
    [ -f "$PACKAGE" ] || fail "Paket bulunamadı: $PACKAGE"
    if [ -f vendor/autoload.php ]; then
        step "Güncelleme öncesi veritabanı yedeği"
        artisan site:backup --no-files || echo "Uyarı: yedek alınamadı, devam ediliyor."
    fi
    step "Paket açılıyor: $PACKAGE"
    extract_package "$PACKAGE"
fi

[ -f vendor/autoload.php ] || fail "vendor/ yok: paket scripts/paket-olustur.ps1 ile oluşturulmalı (sunucuda Composer çalışmaz)."

step "Paket keşfi"
mkdir -p bootstrap/cache storage/framework/{cache/data,sessions,views} storage/logs storage/app/private
# Önceki sürümün config/route/paket önbellekleri yeni kodla uyuşmayabilir; artisan'dan önce silinir.
rm -f bootstrap/cache/*.php
artisan package:discover

if [ "$FIRST_INSTALL" -eq 1 ] && ! grep -qE '^APP_KEY=.+' .env; then
    step "Uygulama anahtarı üretiliyor"
    artisan key:generate --force
fi

step "Veritabanı tabloları"
artisan migrate --force

step "Yükleme klasörü: $PUBLIC_DISK_ROOT"
mkdir -p "$PUBLIC_DISK_ROOT"
cp -f storage/app/public/.htaccess "$PUBLIC_DISK_ROOT/.htaccess"

if [ "$FIRST_INSTALL" -eq 1 ]; then
    step "İlk içerik yükleniyor (yönetici, ayarlar, sayfalar, menüler, kataloglar)"
    artisan db:seed --force
fi

if [ -n "$PUBLIC_PATH" ]; then
    step "Web klasörü eşitleniyor: $PUBLIC_PATH"
    sync_public "$PUBLIC_PATH"
fi

step "Panel dosyaları (Filament)"
artisan filament:assets

step "Önbellekler"
artisan optimize:clear
artisan optimize
artisan filament:optimize

step "Dosya izinleri"
find storage bootstrap/cache -type d -exec chmod 755 {} +
find storage bootstrap/cache -type f -exec chmod 644 {} +
find "$PUBLIC_DISK_ROOT" -type d -exec chmod 755 {} +

if [ "$LIVE" -eq 1 ]; then
    step "Bakım modu kapatılıyor"
    artisan up
    LIVE=0
fi

step "Kurulum kontrolü"
artisan site:check || true

if [ "$FIRST_INSTALL" -eq 1 ]; then
    printf '\n\033[1;33mİlk kurulum bitti. .env içindeki ADMIN_* satırlarını silin, sonra: %s artisan config:cache\033[0m\n' "$PHP"
fi
