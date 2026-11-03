# Dyson Tedarik — cPanel Yayın Rehberi

Bu rehber `dysonted` cPanel hesabının **gerçek koşullarına** göre yazılmıştır (kontrol tarihi 2026-11-03).
Önce **test alt alan adı** (`test.dysontedarik.com`) kurulur ve doğrulanır; canlıya (`dysontedarik.com`)
aynı adımlarla, yalnızca klasör ve `.env` değerleri değiştirilerek geçilir.

```
Yerel bilgisayar (Laragon, PHP 8.3.36)          Hosting (cPanel Terminal)
──────────────────────────────────────          ─────────────────────────
scripts\paket-olustur.ps1                        ~/releases/<paket>.zip
  = son commit + vendor/ + public/build   ──►    bash ~/apps/dysontedarik-test/deploy.sh [--ilk-kurulum] ~/releases/<paket>.zip
                                                 → web dosyaları ~/test.dysontedarik.com içine eşitlenir
```

---

## 0. Bu hostingin koşulları ve alınan önlemler

| Kontrol | Sonuç | Önlem |
|---|---|---|
| cPanel Terminal | Çalışıyor | Kurulum Terminal'den yapılır |
| Terminal'in varsayılan `php` komutu | **PHP 8.2** | `deploy.sh` PHP 8.3'ü kendisi seçer: `/opt/cpanel/ea-php83/root/usr/bin/php`. Elle komutlarda bu yol kullanılır (§3) |
| PHP 8.3 eklentileri | pdo_mysql, mbstring, intl, gd, zip, fileinfo, openssl, curl, bcmath açık | Gereksinimler tamam |
| `proc_open` | **Kapalı** | Sunucuda Composer çalıştırılmaz; `vendor/` yerelde PHP 8.3.36 ile hazırlanıp pakete eklenir. Zamanlanmış görevler ayrı süreç başlatmadan (`Schedule::call`) çalışır |
| `symlink` | **Kapalı** | `storage:link` kullanılmaz. Yüklemeler `PUBLIC_DISK_ROOT` ile doğrudan web klasöründeki gerçek `storage/` klasörüne yazılır |
| Ana domain document root | `/home/dysonted/public_html`, **değiştirilemiyor** | Proje kodu `public_html` dışında durur; `deploy.sh` yalnızca web dosyalarını (`index.php`, `.htaccess`, `build/`, Filament dosyaları) `public_html`'e kopyalar (`PUBLIC_PATH`) |
| Ana domain web PHP'si | **PHP 8.5** | Proje PHP 8.3'te geliştirildi ve test edildi; domain'ler MultiPHP Manager'da **ea-php83**'e alınır (§2). `deploy.sh`, cPanel'in `.htaccess`'e yazdığı PHP satırını korur |
| Git 2.56, `/usr/local/bin/composer` | Mevcut | Composer `proc_open` gerektirdiği için kullanılmaz |
| Dışarıdan SSH (GitHub'dan) | **Doğrulanmadı** | §15'teki iş akışıyla doğrulanana kadar paket elle yüklenir |

---

## 1. Klasör yapısı

```
/home/dysonted/
├── apps/
│   ├── dysontedarik-test/        ← test projesi (kod, .env, vendor, yedekler) — web'den erişilemez
│   └── dysontedarik/             ← canlı proje
├── releases/                     ← yüklenen paketler
├── test.dysontedarik.com/        ← test alt alan adının document root'u (PUBLIC_PATH)
│   ├── index.php  .htaccess  build/  js|css|fonts/filament/  favicon.ico
│   └── storage/                  ← test yüklemeleri (PUBLIC_DISK_ROOT)
└── public_html/                  ← canlı domain'in document root'u (PUBLIC_PATH)
    └── storage/                  ← canlı yüklemeler (PUBLIC_DISK_ROOT)
```

Kurallar (`deploy.sh` bunları denetler ve ihlalde durur):

- Proje klasörü web klasörünün **içinde olamaz** (`.env` ve kaynak kod indirilebilir olurdu).
- Web klasörüne yalnızca derlenmiş/herkese açık dosyalar kopyalanır; içindeki başka dosyalara
  (`.well-known/`, `cgi-bin/`, eski site dosyaları) dokunulmaz.
- Web klasöründeki mevcut `.htaccess` ilk eşitlemede `.htaccess.yedek` olarak saklanır.

---

## 2. cPanel'de hazırlık (test)

1. **Alt alan adı:** cPanel → **Domains → Create A New Domain**
   - Domain: `test.dysontedarik.com`
   - "Share document root" işaretini **kaldırın**; Document Root: `/home/dysonted/test.dysontedarik.com`
     (alt alan adında bu alan düzenlenebilir; ana domain'de düzenlenemiyor).
2. **PHP sürümü:** cPanel → **MultiPHP Manager** → `test.dysontedarik.com` → **PHP 8.3 (ea-php83)** → Apply.
   cPanel bu seçimi web klasörünün `.htaccess` dosyasına bir blok olarak yazar; `deploy.sh` bu bloğu korur.
3. **MultiPHP INI Editor** (ea-php83): `memory_limit = 256M`, `upload_max_filesize = 20M`,
   `post_max_size = 25M`, `max_execution_time = 120`.
4. **Veritabanı:** MySQL Databases →
   - veritabanı `dysonted_test`, kullanıcı `dysonted_testuser` (güçlü şifre),
   - kullanıcıyı veritabanına **ALL PRIVILEGES** ile ekleyin.
5. **E-posta:** Email Accounts → `bildirim@dysontedarik.com`. Connect Devices ekranından SMTP sunucusu ve portunu
   not edin (genellikle `mail.dysontedarik.com`, 465/SSL).
6. **SSL:** SSL/TLS Status → `test.dysontedarik.com` için **Run AutoSSL**.
7. **Erişim kısıtı:** Directory Privacy → `/home/dysonted/test.dysontedarik.com` → şifre koruması. Test sitesi
   `APP_ENV=production` çalıştığı için `robots.txt` onu dizine açar; şifre, arama motorlarının ve ziyaretçilerin
   görmesini engeller.

---

## 3. Terminal'de PHP 8.3 kullanımı

Terminal'in `php` komutu 8.2'dir. Bu oturum için kısa ad tanımlayın (her yeni Terminal'de tekrarlanır):

```bash
alias php83=/opt/cpanel/ea-php83/root/usr/bin/php
php83 -v                       # PHP 8.3.36 görünmeli
```

Bu rehberdeki tüm `artisan` komutları `php83 artisan …` biçimindedir. `deploy.sh` kendi içinde PHP 8.3'ü seçer;
farklı bir yol gerekirse `PHP_BIN=/yol/php bash deploy.sh …` ile verilir.

---

## 4. Yayın paketini hazırlama (yerel bilgisayar)

Laragon terminalinde, proje klasöründe (önce değişiklikleri commit'leyin):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\paket-olustur.ps1
```

Betik:

1. ön yüzü derler (`npm run build`),
2. son commit'i temiz bir geçici klasöre açar (`.env`, testler ve betikler hariç),
3. orada `composer install --no-dev` çalıştırır — `composer.json` platformu **PHP 8.3.36**'ya sabitlenmiştir,
   böylece bağımlılıklar sunucunun PHP'siyle uyumludur,
4. derlenmiş dosyaları ekleyip `release\dysontedarik-<tarih>-<commit>.zip` üretir (~20 MB, ~14.800 dosya).

Betik PHP 8.3 dışında bir sürümle çalıştırılırsa durur.

Paketi cPanel → **File Manager** ile `/home/dysonted/releases/` klasörüne yükleyin.

---

## 5. İlk kurulum (test)

cPanel → **Terminal**:

```bash
mkdir -p ~/apps/dysontedarik-test ~/releases
cd ~/apps/dysontedarik-test
unzip -oq ~/releases/dysontedarik-XXXX.zip deploy.sh .env.production.example deploy/public_html/index.php
cp .env.production.example .env
nano .env                      # §6'daki test değerleri
bash deploy.sh --ilk-kurulum ~/releases/dysontedarik-XXXX.zip
```

`unzip` yoksa ilk üç dosya File Manager → Extract ile çıkarılabilir; `deploy.sh` paketin kendisini `unzip` yoksa
PHP'nin ZipArchive'i ile açar.

`deploy.sh --ilk-kurulum` sırasıyla:

1. PHP 8.3'ü seçer ve sürümü doğrular,
2. `.env`'de `PUBLIC_PATH` ve `PUBLIC_DISK_ROOT`'u denetler (boş, göreli veya güvensiz yol → durur),
3. paketi açar, `vendor/` olduğunu doğrular, paket keşfini yapar,
4. `APP_KEY` üretir, tabloları oluşturur,
5. yükleme klasörünü oluşturup PHP çalıştırmayı engelleyen `.htaccess`'i koyar,
6. ilk içeriği yükler (yönetici, ayarlar, cari bilgiler, sayfalar, menüler, Dyson ve Karbosan katalogları,
   kurumsal metinler; görseller doğrudan `PUBLIC_DISK_ROOT`'a yazılır),
7. web dosyalarını `PUBLIC_PATH`'e eşitler (`index.php` proje yolu yazılarak, `.htaccess` cPanel PHP bloğu
   korunarak), Filament dosyalarını yayımlar,
8. önbellekleri oluşturur, izinleri ayarlar, `site:check` çalıştırır.

Bitince:

1. `.env` içindeki `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` satırlarını **silin**, sonra `php83 artisan config:cache`.
2. `https://test.dysontedarik.com/admin` → giriş → şifreyi panelden değiştirin.
3. §7 cron satırını ekleyin.
4. §10 kontrol listesini uygulayın.

---

## 6. `.env` değerleri

Şablon: `.env.production.example`. `.env` hiçbir zaman pakete veya git'e girmez.

| Alan | Test | Canlı |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | `production` / `false` |
| `APP_URL` | `https://test.dysontedarik.com` | `https://www.dysontedarik.com` (veya www'suz; tek adres seçilir) |
| `PUBLIC_PATH` | `/home/dysonted/test.dysontedarik.com` | `/home/dysonted/public_html` |
| `PUBLIC_DISK_ROOT` | `/home/dysonted/test.dysontedarik.com/storage` | `/home/dysonted/public_html/storage` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `dysonted_test` / `dysonted_testuser` / … | canlı veritabanı |
| `MAIL_*` | `bildirim@dysontedarik.com` SMTP bilgileri (465 → `MAIL_SCHEME=smtps`) | aynı |
| `SESSION_SECURE_COOKIE` | `true` | `true` |
| `TRUSTED_PROXIES` | boş (Cloudflare varsa `*`) | aynı |

- `APP_URL` kanonik adrestir; production'da http ve diğer host adı istekleri buraya 301 ile yönlendirilir.
  Test sunucusuna canlı adres yazılırsa test sitesi canlıya yönlenir.
- `.env` değişikliğinden sonra: `php83 artisan config:cache`.
- `.env` dosya izni: önce `chmod 640 .env` deneyin; site açılmıyorsa sunucu PHP'yi başka kullanıcıyla
  çalıştırıyordur, `644`'e dönün.

---

## 7. Cron

cPanel → **Cron Jobs** → Once Per Minute:

```
* * * * * /opt/cpanel/ea-php83/root/usr/bin/php /home/dysonted/apps/dysontedarik-test/artisan schedule:run >> /dev/null 2>&1
```

PHP yolu **mutlaka** ea-php83 olmalı (varsayılan `php` 8.2'dir). Görevler:

| Görev | Zaman |
|---|---|
| Kalp atışı (`site:check` cron'un çalıştığını buradan anlar) | Her dakika |
| `site:backup` — veritabanı + yüklenen dosyalar, son 14 yedek | Her gün 03:30 |
| Süresi dolmuş şifre sıfırlama bağlantılarını temizleme | Her gün 00:00 |

Görevler `Schedule::call` ile aynı PHP sürecinde çalışır; `proc_open` gerekmez. Kuyruk işçisi yoktur.

---

## 8. Yüklenen dosyalar

Panelden yüklenen görsel ve PDF'ler `PUBLIC_DISK_ROOT` klasörüne yazılır ve `https://ALANADI/storage/…`
adresinden sunulur. Sembolik bağlantı yoktur. Klasördeki `.htaccess` dosyası PHP çalıştırmayı engeller ve her
`deploy.sh` çalışmasında yenilenir. Yedekleme bu klasörü kullanır.

---

## 9. Kurulum doğrulaması (yerel prova)

Bu akış 2026-11-03'te yerelde, hostingin kısıtları taklit edilerek denendi:

- PHP `disable_functions=proc_open,symlink,popen,exec,shell_exec,passthru,system`, PATH'te Composer yok,
- proje `apps/dysontedarik-test`, web klasörü ayrı; web klasöründe cPanel PHP bloğu olan `.htaccess` ve
  `.well-known/` önceden mevcut.

Sonuç: ilk kurulum ve güncelleme hatasız tamamlandı; ana sayfa, ürünler, kategori, ürün detayı, teklif, iletişim,
sitemap, `/admin/login`, ürün görseli, derlenmiş CSS, Filament JS ve `.well-known` dosyası 200 döndü; olmayan sayfa
404; cPanel PHP bloğu korundu ve tekrar eşitlemede çoğalmadı; web klasöründe `.env` yok; cron ve tam yedek
(46 dosya) çalıştı. **Gerçek hostingde henüz denenmedi**: §10 orada uygulanmalı.

---

## 10. Test sunucusunda kontrol listesi

```bash
cd ~/apps/dysontedarik-test && php83 artisan site:check
```

Kritik sorun kırmızı ✘ ile gösterilir. Ardından tarayıcıda:

- [ ] `http://test.dysontedarik.com` → `https://` adrese 301.
- [ ] Ana sayfa, slaytlar, kategori ve ürün görselleri açılıyor.
- [ ] `/admin` girişi; bir ürüne JPG yükleyince WebP'ye çevrilip `…/storage/products/…` altında görünüyor.
- [ ] Site Ayarları → **Test e-postası gönder** başarılı ve e-posta geliyor.
- [ ] Teklif ve iletişim formları gönderiliyor, bildirim e-postası geliyor, kayıt panelde.
- [ ] `/sitemap.xml`, `/robots.txt` açılıyor.
- [ ] `https://test.dysontedarik.com/.env`, `/storage/x.php` gibi adresler 403/404 (proje kodu web klasöründe değil).
- [ ] `/olmayan-sayfa` markalı 404 sayfası, hata ayrıntısı yok.
- [ ] cPanel → MultiPHP Manager'da domain hâlâ ea-php83 (eşitleme `.htaccess` bloğunu bozmadı).
- [ ] Ertesi sabah `~/apps/dysontedarik-test/storage/app/private/backups` altında yedek var.
- [ ] Lighthouse (Chrome DevTools, mobil + masaüstü): ana sayfa, kategori, ürün.

---

## 11. Güncelleme

1. Yerelde commit → `scripts\paket-olustur.ps1` → paketi `~/releases/`'e yükle.
2. Terminal:

```bash
cd ~/apps/dysontedarik-test
bash deploy.sh ~/releases/dysontedarik-YYYYMMDD-HHMM-abc1234.zip
```

Bakım modu → veritabanı yedeği → paket → eski önbellek dosyalarının silinmesi → migration → web klasörü eşitleme →
önbellek → canlı. Seed çalışmaz; panel içeriği korunur. Bir adım hata verirse site bakım modunda kalır;
düzeltip betiği yeniden çalıştırın veya `php83 artisan up`.

Pakette olmayan eski dosyalar proje klasöründe kalır; çalışmayı etkilemez. `build/` klasörü web klasöründe her
seferinde silinip yeniden kopyalanır.

---

## 12. Yedekleme ve geri yükleme

Yedek: `veritabani.sql` + `dosyalar/` (yüklemeler). `.env` yedeğe **girmez**; ayrı ve güvenli saklanmalıdır.

```bash
php83 artisan site:backup            # veritabanı + dosyalar
php83 artisan site:backup --no-files # yalnızca veritabanı
```

Yedekler `storage/app/private/backups/` altındadır, web'den erişilemez. Haftada bir File Manager ile indirilip
hosting dışında saklanmalıdır.

Geri yükleme:

```bash
cd ~/apps/dysontedarik-test
php83 artisan down
mkdir -p /tmp/geri && cd /tmp/geri && unzip -oq ~/apps/dysontedarik-test/storage/app/private/backups/yedek-YYYYMMDD-HHMMSS.zip
mysql -u dysonted_testuser -p dysonted_test < /tmp/geri/veritabani.sql
cp -r /tmp/geri/dosyalar/. /home/dysonted/test.dysontedarik.com/storage/
cd ~/apps/dysontedarik-test && php83 artisan optimize:clear && php83 artisan optimize && php83 artisan up
rm -rf /tmp/geri
```

`mysql` komutu yoksa `veritabani.sql` phpMyAdmin → İçe Aktar ile yüklenir.

Sürüm geri alma: önceki paketi `bash deploy.sh ~/releases/<önceki>.zip` ile kurun; yeni sürüm migration
içeriyorsa güncelleme öncesi alınan yedeği de geri yükleyin.

---

## 13. Sorun giderme

| Belirti | Olası neden | Çözüm |
|---|---|---|
| `deploy.sh`: "PHP 8.3+ gerekli" | Terminal'in php'si 8.2 ve ea-php83 bulunamadı | `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy.sh …` |
| `deploy.sh`: "vendor/ yok" | Paket eski betikle veya elle hazırlanmış | Paketi `scripts\paket-olustur.ps1` ile yeniden oluşturun |
| Tüm sitede 500 | `.htaccess` kuralı izinli değil veya PHP sürümü 8.5'e döndü | cPanel → Errors; MultiPHP Manager'da ea-php83'ü yeniden seçin; `PUBLIC_DISK_ROOT/.htaccess` içindeki `Options` / `php_flag` satırlarını silip deneyin |
| `Composer detected issues in your platform` | Web PHP'si 8.3 değil | MultiPHP Manager → ea-php83 |
| Beyaz sayfa / "Server Error" | Uygulama hatası | `~/apps/dysontedarik-test/storage/logs/laravel-YYYY-MM-DD.log` |
| Görseller 404 | `PUBLIC_DISK_ROOT` web klasörünün dışında | `site:check` → "Yüklemeler web klasöründe"; `.env` düzeltilip `bash deploy.sh` |
| Stil yok | `build/` web klasörüne kopyalanmamış | `bash deploy.sh` yeniden; `site:check` → "Derlenmiş ön yüz dosyaları" |
| Panel stilleri yok | Filament dosyaları | `php83 artisan filament:assets` |
| "419 Page Expired" | Çerez alınamıyor | `APP_URL` gerçek adresle aynı mı; https açık mı |
| Sonsuz yönlendirme | Proxy https bilgisini iletmiyor | `TRUSTED_PROXIES=*`, `php83 artisan config:cache` |
| Cron çalışmıyor | Cron satırında php 8.2 | Satırda `/opt/cpanel/ea-php83/root/usr/bin/php` kullanın; `site:check` → "Cron çalışıyor" |
| E-posta gelmiyor | SMTP bilgisi / port | Panel → Test e-postası; log'da "e-posta gönderilemedi" |

---

## 14. Canlıya geçiş

1. Test sunucusunda §10'un tamamı geçti.
2. KVKK Aydınlatma Metni, Gizlilik ve Çerez Politikası yayında.
3. Kanonik adres kararı (`www` / `www`'suz) verildi.
4. MultiPHP Manager → `dysontedarik.com` → **ea-php83** (şu an 8.5).
5. `public_html` içeriğini yedekleyin (File Manager → Compress); mevcut site varsa eski adresleri listeleyin.
6. Canlı veritabanı (`dysonted_canli`), `~/apps/dysontedarik` klasörü, `.env` (`PUBLIC_PATH=/home/dysonted/public_html`,
   `PUBLIC_DISK_ROOT=/home/dysonted/public_html/storage`).
7. `bash ~/apps/dysontedarik/deploy.sh --ilk-kurulum ~/releases/<paket>.zip`.
8. Test sunucusundaki içerik taşınacaksa: testte `site:backup`, canlıda §12'deki gibi geri yükleme.
9. Cron satırı canlı proje yoluyla; SSL; Search Console doğrulama + `sitemap.xml` gönderimi; eski adresler için
   Panel → Yönlendirmeler (301).
10. İlk hafta `storage/logs` ve Teklif Talepleri günlük kontrol.

---

## 15. Otomatik yayın: GitHub'dan SSH erişiminin doğrulanması

cPanel **Terminal**'in çalışması, sunucuya **dışarıdan** SSH ile bağlanılabildiğini göstermez: hosting SSH'ı
kapatmış, farklı bir porta almış veya yalnızca izinli IP'lere açmış olabilir. GitHub'ın makineleri her çalışmada
farklı IP'lerden gelir. Bu yüzden otomatik yayın, aşağıdaki doğrulama **GitHub'ın kendi makinesinden**
başarılı olana kadar kullanılmaz; o zamana kadar paket File Manager ile yüklenir.

**Ön bilgi (cPanel'den):**

- cPanel → **SSH Access** sayfası var mı? Yoksa hesapta dışarıdan SSH kapalıdır; hosting firmasına açtırılmalı.
- SSH sunucu adı ve **port** (hosting dokümanı veya destek; çoğunlukla 22 değildir).

**Adımlar:**

1. Yerelde yalnızca bu iş için anahtar üretin (şifresiz, başka yerde kullanılmaz):
   ```powershell
   ssh-keygen -t ed25519 -C "github-yayin-dysontedarik" -f $env:USERPROFILE\.ssh\dysontedarik_github -N '""'
   ```
2. cPanel → SSH Access → Manage SSH Keys → **Import Key**: `dysontedarik_github.pub` içeriği → **Authorize**.
3. **Sunucu kimliğini doğrulayın** (ilk bağlantıda körü körüne güvenilmez):
   - cPanel Terminal: `for f in /etc/ssh/ssh_host_*_key.pub; do ssh-keygen -lf "$f"; done` → parmak izlerini not edin.
   - Yerelde: `ssh-keyscan -p PORT SUNUCU > known_hosts.txt` ve `ssh-keygen -lf known_hosts.txt` → parmak izleri
     cPanel'dekilerle **aynı** olmalı.
4. **Önce yerelden deneyin** (dışarıdan erişimin ilk kanıtı):
   ```powershell
   ssh -i $env:USERPROFILE\.ssh\dysontedarik_github -p PORT dysonted@SUNUCU "echo SSH_OK; /opt/cpanel/ea-php83/root/usr/bin/php -v"
   ```
5. Projeyi GitHub'a gönderin (özel depo). Settings → **Environments** → `test`:
   `SSH_HOST`, `SSH_PORT`, `SSH_USER` (`dysonted`), `SSH_PRIVATE_KEY` (özel anahtarın tamamı),
   `SSH_KNOWN_HOSTS` (`known_hosts.txt` içeriği), `APP_DIR` (`/home/dysonted/apps/dysontedarik-test`).
6. Actions → **SSH doğrulama** → Run workflow (`test`). İş akışı sırasıyla dener:
   TCP portu → SSH ile komut çalıştırma (PHP 8.3 sürümü ve `disable_functions` raporlanır) → `scp` ile dosya
   yazma ve geri okuma. Tümü yeşilse GitHub'dan SSH **doğrulanmış** olur.
7. Ancak bundan sonra Actions → **Yayın** (`test`) kullanılır: testleri çalıştırır, paketi Ubuntu + PHP 8.3'te
   hazırlar (vendor dahil), `~/releases/`'e kopyalar ve `deploy.sh`'i çalıştırır. `canli` ortamı için GitHub'da
   "Required reviewers" koruması eklenmelidir.

**Doğrulama başarısız olursa:**

| Adım | Anlamı | Seçenek |
|---|---|---|
| TCP portu kapalı | SSH dışarıya kapalı veya IP kısıtlı | Hostingden SSH erişimi/port bilgisi isteyin; IP kısıtı kaldırılamıyorsa GitHub'ın barındırılan makineleri kullanılamaz |
| Port açık, giriş reddedildi | Anahtar yetkilendirilmemiş veya kullanıcı yanlış | SSH Access → Authorize; `SSH_USER` |
| Host anahtarı uyuşmuyor | `SSH_KNOWN_HOSTS` hatalı veya bağlantı araya giren biri tarafından değiştiriliyor | 3. adımı tekrarlayın; parmak izleri uyuşmadan devam etmeyin |
| SSH çalışıyor, scp başarısız | Hesap kısıtlı kabuk (jailshell) | Hostingden normal kabuk isteyin veya paketi elle yükleyin |

SSH dışarıdan hiç açılamıyorsa yayın elle sürdürülür (paket → File Manager → Terminal'de `deploy.sh`); bu akış
otomatik yayınla aynı betiği kullanır.
