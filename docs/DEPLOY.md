# Dyson Tedarik — cPanel Yayın Rehberi

Bu rehber siteyi önce **test sunucusuna** (ör. `test.dysontedarik.com`), doğrulandıktan sonra **canlıya**
(`dysontedarik.com`) kurmayı anlatır. İki kurulum aynı adımlarla yapılır; yalnızca `.env` değerleri farklıdır.

```
Yerel bilgisayar                          Sunucu (cPanel, SSH)
────────────────                          ────────────────────
scripts\paket-olustur.ps1  ──  zip  ──►   ~/releases/paket.zip
                                          bash ~/dysontedarik/deploy.sh [--ilk-kurulum] ~/releases/paket.zip
                                          php artisan site:check
```

---

## 1. Sunucu gereksinimleri

Hosting firmasına kurulumdan önce sorulacaklar:

| Gereksinim | Neden | Kontrol |
|---|---|---|
| PHP **8.3** veya üstü (web ve komut satırı) | Laravel 13 | cPanel → MultiPHP Manager; SSH'ta `php -v` |
| PHP eklentileri: `pdo_mysql`, `mbstring`, `intl`, `gd` (WebP destekli), `zip`, `fileinfo`, `openssl`, `curl`, `bcmath` | Panel, görsel işleme, Excel aktarımı, yedek | cPanel → Select PHP Version → Extensions; SSH'ta `php -m` |
| `memory_limit` ≥ 256M, `upload_max_filesize` ≥ 20M, `post_max_size` ≥ 25M, `max_execution_time` ≥ 120 | Görsel ve Excel yükleme | MultiPHP INI Editor |
| `proc_open`, `symlink` fonksiyonları açık | Composer ve `storage:link` | `php -r "var_dump(function_exists('proc_open'), function_exists('symlink'));"` |
| MySQL 8 veya MariaDB 10.6+ | Veritabanı | cPanel → MySQL Databases |
| **SSH** veya cPanel **Terminal** erişimi | `composer`, `artisan` komutları | cPanel → SSH Access / Terminal |
| Composer 2 | Bağımlılıklar | `composer -V` (yoksa bkz. §9) |
| SSL (AutoSSL / Let's Encrypt) | HTTPS zorunlu | cPanel → SSL/TLS Status |
| Domain'in **document root**'unu değiştirebilme | Güvenlik (bkz. §2) | cPanel → Domains |

Node.js sunucuda **gerekmez**: ön yüz dosyaları yerelde derlenip pakete eklenir.

---

## 2. Klasör yapısı ve document root

Proje kodu, `.env` ve yedekler **web'den erişilemeyen** bir klasörde durmalı. Ziyaretçinin erişebildiği tek klasör
`public/` olmalı.

```
/home/KULLANICI/
├── dysontedarik/          ← proje (git archive paketi buraya açılır)
│   ├── app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/
│   ├── public/            ← web'e açık tek klasör
│   ├── .env               ← gizli ayarlar (pakette YOK, sunucuda elle oluşturulur)
│   └── deploy.sh
├── releases/              ← yüklenen paketler
└── public_html/           ← cPanel'in varsayılan web klasörü
```

Aşağıdaki yöntemlerden **ilk uygulanabilen** seçilir:

### Yöntem A — Document root'u `public/` yap (önerilen)

cPanel → **Domains** → ilgili domain veya alt alan adı → **Document Root**:
`/home/KULLANICI/dysontedarik/public`

Test için alt alan adı oluştururken (`test.dysontedarik.com`) document root doğrudan
`/home/KULLANICI/dysontedarik-test/public` girilebilir.

### Yöntem B — `public_html`'i sembolik bağlantı yap

Ana domain'in document root'u kilitliyse (bazı paketlerde `public_html` sabittir), SSH'ta:

```bash
mv ~/public_html ~/public_html_eski      # içindekileri önce yedekleyin
ln -s ~/dysontedarik/public ~/public_html
```

### Yöntem C — `public/` içeriğini `public_html`'e kopyala (son çare)

Sembolik bağlantı da yasaksa:

1. `~/dysontedarik/public/` içindeki her şeyi (`.htaccess` dahil) `~/public_html/` içine kopyalayın.
2. `~/public_html/index.php` dosyasını `deploy/public_html/index.php` ile değiştirin (proje yolunu
   `~/dysontedarik` olarak arar ve `public_html`'i public klasörü kabul eder).
3. `.env` içinde `PUBLIC_DISK_ROOT=/home/KULLANICI/public_html/storage` tanımlayın (bkz. §8).
4. Her güncellemede `public/build/` klasörünü `public_html/build/` içine tekrar kopyalayın.

> Hiçbir yöntemde proje klasörünün tamamı `public_html` içine **konmaz**: `.env` ve kaynak kod web'den indirilebilir hâle gelir.

---

## 3. cPanel'de hazırlık

1. **PHP sürümü:** MultiPHP Manager → domain için `ea-php83` (veya üstü).
2. **Veritabanı:** MySQL Databases →
   - veritabanı: `KULLANICI_dysontedarik` (test için `KULLANICI_dysontest`),
   - kullanıcı: `KULLANICI_dtuser`, güçlü şifre,
   - kullanıcıyı veritabanına **ALL PRIVILEGES** ile ekleyin.
3. **E-posta hesabı:** Email Accounts → `bildirim@dysontedarik.com` (site bildirimlerini bu hesap gönderir).
   Connect Devices ekranındaki SMTP sunucusu ve portu not edin (genellikle `mail.dysontedarik.com`, 465/SSL).
4. **SSL:** SSL/TLS Status → domain ve alt alan adı için AutoSSL çalıştırın.
5. **SSH:** SSH Access → anahtar ekleyin veya cPanel → Terminal kullanın.

---

## 4. Yayın paketini hazırlama (yerel bilgisayar)

Laragon terminalinde proje klasöründe:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\paket-olustur.ps1
```

`release\dysontedarik-<tarih>-<commit>.zip` oluşur. İçinde son commit'teki dosyalar ve derlenmiş `public/build`
vardır; `.env`, `vendor`, `node_modules`, testler ve yerel yüklemeler **yoktur**. Paket yalnızca commit'lenmiş
değişiklikleri içerir; önce commit atın.

Paketi cPanel → File Manager veya `scp` ile `~/releases/` klasörüne yükleyin:

```bash
scp release/dysontedarik-*.zip KULLANICI@SUNUCU:~/releases/
```

---

## 5. İlk kurulum

SSH'ta:

```bash
mkdir -p ~/dysontedarik ~/releases
cd ~/dysontedarik
unzip -oq ~/releases/dysontedarik-XXXX.zip        # deploy.sh ve .env şablonu için önce bir kez aç
cp .env.production.example .env
nano .env                                          # §6'daki alanları doldurun
```

Ardından:

```bash
bash deploy.sh --ilk-kurulum
```

Betik sırasıyla: Composer bağımlılıklarını kurar, `APP_KEY` üretir, tabloları oluşturur, ilk içeriği yükler
(yönetici, ayarlar, cari bilgiler, sayfalar, menüler, Dyson ve Karbosan katalogları, kurumsal metinler),
yükleme klasörünü bağlar, önbellekleri oluşturur, izinleri ayarlar ve `site:check` çalıştırır.

Bitince:

1. `.env` içindeki `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` satırlarını **silin**, sonra `php artisan config:cache`.
2. `https://ALANADI/admin` adresinden giriş yapıp şifreyi panelden değiştirin.
3. §7'deki cron satırını ekleyin.
4. §10'daki kontrol listesini uygulayın.

> PHP komutu sunucuda farklı adlıysa: `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy.sh --ilk-kurulum`

---

## 6. `.env` — doldurulacak alanlar

Şablon: `.env.production.example`. Bu dosya **hiçbir zaman** pakete veya git'e girmez.

| Alan | Test sunucusu | Canlı |
|---|---|---|
| `APP_ENV` | `production` | `production` |
| `APP_DEBUG` | `false` | `false` — **asla true değil** |
| `APP_URL` | `https://test.dysontedarik.com` | `https://www.dysontedarik.com` (veya www'suz; tek adres seçilir) |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | test veritabanı | canlı veritabanı |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_SCHEME` | cPanel e-posta bilgileri (465 → `smtps`, 587 → `smtp`) | aynı |
| `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_FROM_ADDRESS` | `bildirim@dysontedarik.com` | aynı |
| `SESSION_SECURE_COOKIE` | `true` | `true` |
| `TRUSTED_PROXIES` | boş (Cloudflare kullanılıyorsa `*`) | aynı |
| `PUBLIC_DISK_ROOT` | boş (yalnızca §8 durumunda) | boş |

Notlar:

- `APP_URL` tüm sayfaların kanonik adresidir. Canlıda http ve diğer host adı (www / www'suz) istekleri bu adrese
  **301** ile yönlendirilir. Test sunucusunda kendi adresi yazılmalıdır; aksi hâlde test sitesi canlıya yönlenir.
- `APP_ENV=production` olmayan ortamlarda `robots.txt` tüm siteyi arama motorlarına kapatır. Test sunucusu
  `production` çalıştığı için ek koruma olarak cPanel → **Directory Privacy** ile şifre koyun.
- `.env` değiştikten sonra: `php artisan config:cache`.

---

## 7. Cron (zamanlanmış görevler)

cPanel → **Cron Jobs** → "Once Per Minute":

```
* * * * * /usr/local/bin/php /home/KULLANICI/dysontedarik/artisan schedule:run >> /dev/null 2>&1
```

(`/usr/local/bin/php` yerine hostingin PHP 8.3 yolu: çoğu sunucuda `/opt/cpanel/ea-php83/root/usr/bin/php`.)

Bu tek satır şunları çalıştırır:

| Görev | Zaman |
|---|---|
| `site:backup` — veritabanı + yüklenen dosyalar yedeği, son 14 yedek saklanır | Her gün 03:30 |
| Önbellek ve model temizliği | Her gün 00:00 |
| Cron kalp atışı (`site:check` cron'un çalıştığını buradan anlar) | Her dakika |

Kuyruk işçisi **gerekmez**: e-postalar yanıt gönderildikten sonra aynı istekte gönderilir.

---

## 8. Yüklenen dosyalar ve `storage:link`

Panelden yüklenen görseller `storage/app/public` altına yazılır ve `public/storage` sembolik bağlantısıyla sunulur.
`deploy.sh` bağlantıyı otomatik kurar.

**`storage:link` çalışmıyorsa** (hosting `symlink()` fonksiyonunu kapatmışsa) görseller sitede görünmez. Çözüm:

```bash
mkdir -p ~/dysontedarik/public/storage
cp -r ~/dysontedarik/storage/app/public/. ~/dysontedarik/public/storage/
echo 'PUBLIC_DISK_ROOT=/home/KULLANICI/dysontedarik/public/storage' >> ~/dysontedarik/.env
php artisan config:cache
```

Bundan sonra yüklemeler doğrudan bu gerçek klasöre yazılır; `deploy.sh` klasöre PHP çalıştırmayı engelleyen
`.htaccess` dosyasını da kopyalar. Yedekleme bu klasörü otomatik kullanır.

---

## 9. Composer sunucuda yoksa

```bash
cd ~/dysontedarik
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --2 && rm composer-setup.php      # composer.phar oluşur
```

`deploy.sh`, `composer` komutu bulunamazsa proje kökündeki `composer.phar` dosyasını kullanır.

---

## 10. Kurulum sonrası kontrol listesi

```bash
php artisan site:check
```

Kritik bir sorun varsa komut kırmızı ✘ ile belirtir ve 1 ile çıkar. Ardından tarayıcıda:

- [ ] `http://` adresi `https://` adrese, www / www'suz diğer adres `APP_URL`'e 301 ile gidiyor.
- [ ] Ana sayfa, slaytlar, kategori ve ürün görselleri açılıyor (görsel yoksa §8).
- [ ] `/admin` girişi çalışıyor; bir ürüne görsel yükleyince WebP'ye çevrilip görünüyor.
- [ ] Panel → Site Ayarları → **Test e-postası gönder** başarılı ve e-posta geliyor.
- [ ] Teklif formu ve iletişim formu gönderiliyor; bildirim e-postası geliyor; kayıt panelde görünüyor.
- [ ] `/sitemap.xml` ve `/robots.txt` açılıyor (canlıda robots `Disallow: /admin` + sitemap adresi içerir).
- [ ] `https://ALANADI/.env`, `/.git/config`, `/composer.json` adresleri **403 veya 404** dönüyor.
- [ ] Var olmayan bir adres markalı 404 sayfası gösteriyor, hata ayrıntısı göstermiyor.
- [ ] Ertesi sabah `storage/app/private/backups` altında yedek dosyası oluşmuş.
- [ ] Lighthouse (Chrome → DevTools → Lighthouse, mobil + masaüstü) ana sayfa, kategori ve ürün sayfalarında ölçüldü.

---

## 11. Güncelleme (yeni sürüm yayınlama)

1. Yerelde değişiklikleri commit'leyin, `scripts\paket-olustur.ps1` ile paket oluşturun.
2. Paketi `~/releases/` klasörüne yükleyin.
3. Sunucuda:

```bash
cd ~/dysontedarik
bash deploy.sh ~/releases/dysontedarik-YYYYMMDD-HHMM-abc1234.zip
```

Betik: siteyi bakım moduna alır, **veritabanı yedeği alır**, paketi açar, bağımlılıkları kurar, yeni migration'ları
çalıştırır, önbellekleri yeniler ve bakım modunu kapatır. Seed çalıştırılmaz; panelde yapılan içerik değişiklikleri
korunur.

Paket açılırken eski sürümde olup yeni sürümde silinen dosyalar yerinde kalır; bu çalışmayı etkilemez. Temiz
kurulum gerekirse proje klasörü `.env` ve `storage/` korunarak yeniden oluşturulur.

Bir adım hata verirse site bakım modunda kalır. Sorun giderilince betik yeniden çalıştırılır veya
`php artisan up` ile site açılır.

---

## 12. Yedekleme ve geri yükleme

**Yedek içeriği:** `veritabani.sql` (tüm tablolar) ve `dosyalar/` (panelden yüklenen tüm görsel ve PDF'ler).
`.env` bilerek yedeğe **eklenmez**: ayrı ve güvenli bir yerde (ör. şifre yöneticisi) saklanmalıdır.

Elle yedek:

```bash
php artisan site:backup            # veritabanı + dosyalar
php artisan site:backup --no-files # yalnızca veritabanı
```

Yedekler `~/dysontedarik/storage/app/private/backups/` altındadır ve web'den erişilemez. Sunucu arızasına karşı
haftada bir yerel bilgisayara veya başka bir depolamaya indirilmelidir (cPanel → File Manager veya `scp`).

**Geri yükleme:**

```bash
cd ~/dysontedarik
php artisan down
unzip yedek-YYYYMMDD-HHMMSS.zip -d /tmp/geri-yukle
mysql -u KULLANICI_dtuser -p KULLANICI_dysontedarik < /tmp/geri-yukle/veritabani.sql
cp -r /tmp/geri-yukle/dosyalar/. storage/app/public/     # PUBLIC_DISK_ROOT varsa o klasöre
php artisan optimize:clear && php artisan optimize
php artisan up
rm -rf /tmp/geri-yukle
```

SSH yoksa `veritabani.sql`, cPanel → phpMyAdmin → İçe Aktar ile yüklenebilir.

**Sürüm geri alma:** önceki paketi `bash deploy.sh ~/releases/<önceki paket>.zip` ile yeniden yükleyin. Yeni sürüm
veritabanında değişiklik yaptıysa (yeni migration) güncelleme öncesi alınan yedeği de geri yükleyin.

---

## 13. Sorun giderme

| Belirti | Olası neden | Çözüm |
|---|---|---|
| Tüm sitede **500 Internal Server Error** | `.htaccess` içindeki bir kural hostingde izinli değil | `~/logs/` veya cPanel → Errors. `storage/app/public/.htaccess` içindeki `Options` / `php_flag` satırlarını kaldırıp deneyin; `public/.htaccess` içindeki `<If>` bloğunu kaldırıp deneyin |
| Beyaz sayfa veya "Server Error" | Uygulama hatası | `storage/logs/laravel-YYYY-MM-DD.log` |
| Görseller açılmıyor (404) | `public/storage` bağlantısı yok | §8 |
| Görsel yükleme hatası | `upload_max_filesize` küçük veya klasör yazılamıyor | §1 INI ayarları; `php artisan site:check` |
| Stil yok, sayfa düz HTML | `public/build` eksik | Paket `scripts\paket-olustur.ps1` ile mi oluşturuldu? Yöntem C ise `build/` kopyalandı mı? |
| Panel stilleri yok | Filament dosyaları yayımlanmamış | `php artisan filament:assets` |
| E-posta gelmiyor | SMTP bilgileri / port | Panel → Test e-postası; `storage/logs` içinde "e-posta gönderilemedi" kaydı |
| Formlar "419 Page Expired" | Oturum çerezi alınamıyor | `APP_URL` gerçek adresle aynı mı, `SESSION_SECURE_COOKIE=true` iken site https mi? |
| Sonsuz yönlendirme döngüsü | Cloudflare / proxy arkasında https algılanmıyor | `.env` → `TRUSTED_PROXIES=*`, sonra `php artisan config:cache` |
| `.env` değişikliği etkisiz | Config önbelleği | `php artisan config:cache` |
| Cron çalışmıyor | PHP yolu yanlış | Cron satırındaki PHP yolunu `which php` / ea-php83 yolu ile değiştirin; `site:check` "Cron çalışıyor" satırına bakın |

---

## 14. Canlıya geçiş

1. Test sunucusunda §10'daki tüm maddeler tamam.
2. KVKK Aydınlatma Metni, Gizlilik ve Çerez Politikası yayında (formlardaki onay kutusu KVKK sayfasına bağlanır).
3. Canlı için ayrı veritabanı ve `.env` (§3, §6); kanonik adres kararı verilmiş (`www` veya `www`'suz).
4. `bash deploy.sh --ilk-kurulum ~/releases/<paket>.zip` (canlı klasöründe).
5. Test sunucusunda girilen içerik canlıya taşınacaksa: test sunucusunda `php artisan site:backup`, yedeği
   canlıda §12'deki gibi geri yükleyin (ilk kurulumdaki seed içeriğinin yerine geçer).
6. DNS'in canlı sunucuya yönlendiğini ve SSL'in aktif olduğunu doğrulayın.
7. Google Search Console'da alan adını doğrulayın (doğrulama kodu: Panel → Site Ayarları → Entegrasyonlar) ve
   `https://ALANADI/sitemap.xml` adresini gönderin.
8. Eski bir site varsa eski adresleri Panel → Yönlendirmeler (301) ekranından yeni adreslere yönlendirin.
9. İlk hafta `storage/logs` ve Panel → Teklif Talepleri günlük kontrol edilir.
