# Dyson Tedarik — İlerleme

Son güncelleme: 2026-11-02

## Aşama durumu

| Aşama | Durum |
|---|---|
| 1 — Temel altyapı (Laravel 13.39, Filament 5.11, MySQL 8.4, giriş, roller) | ✅ Tamamlandı |
| 2 — CMS (ana sayfa blokları, kurumsal sayfalar, menü, footer, site ayarları) | ✅ Panel tarafı tamamlandı |
| 3 — Ürün kataloğu (ürün, kategori, marka, özellik, Excel/CSV, katalog sorgu servisi) | ✅ Tamamlandı |
| 4 — Ön yüz (Blade + Tailwind 4 + Alpine) | ✅ Tamamlandı (teklif/iletişim formları Aşama 5'te) |
| 5 — SEO ve iletişim (teklif/iletişim formları, SMTP bildirimi, sitemap, robots, 301) | ✅ Tamamlandı |
| 6 — Test ve cPanel yayını | ⏳ Başlanmadı |

## Tamamlanan admin modülleri (`/admin`)

| Modül | Yetki | Not |
|---|---|---|
| Panel (Dashboard) | Herkes | Yeni teklif/mesaj, ürün ve sayfa sayıları |
| Ana Sayfa Yönetimi | Yönetici, editör | 14 blok türü, sürükle-bırak, aktif/pasif, cihaz, varyant, taslak → yayınla, taslağı geri al |
| Kurumsal Sayfalar | Yönetici, editör | 10 başlangıç sayfası (taslak, içeriksiz), zengin metin, içerik blokları, SEO, menüde göster, zamanlı yayın, çöp kutusu |
| Slider / Banner | Yönetici, editör | Masaüstü + mobil görsel, buton, sıra (sürükle), tarih aralığı, hero/promo grubu |
| Medya Kütüphanesi | Yönetici, editör | JPG/PNG/WEBP/PDF, klasör, ALT, adres kopyala |
| Teklif Talepleri / İletişim Mesajları | Görüntüle+güncelle: ikisi; sil: yönetici | Yeni/İnceleniyor/Cevaplandı, yönetici notu, durum ve tarih filtresi |
| Menü Yönetimi | Yönetici, editör | 5 konum, 3 seviye, sayfa/kategori/marka/sistem sayfası/harici bağlantı, mega menü işareti |
| Footer Yönetimi | Yönetici, editör | Açıklama, telif yazısı, gösterilecek sütunlar |
| Yönlendirmeler (301/302) | Ekle/düzenle: ikisi; sil: yönetici | Yol normalizasyonu, kullanım sayacı alanları |
| Site Ayarları | Yalnızca yönetici | Genel, iletişim, sosyal medya, varsayılan SEO, Analytics/GTM/Search Console kimlikleri (yalnızca kimlik, script değil) |
| Kullanıcılar ve Roller | Yalnızca yönetici | Yönetici/editör, pasifleştirme, kendini silme/rol düşürme engeli |

## Aşama 3 — Katalog modülleri

| Modül | Özellikler |
|---|---|
| Ürünler | Ad, benzersiz kod, slug, marka (formdan yeni marka eklenebilir), ana + ek kategoriler, kısa/detaylı açıklama, kullanım alanları, teknik özellik tablosu, renk/hacim/ambalaj, filtre özellikleri, sıralanabilir çoklu görsel (ALT ile), PDF dokümanlar, öne çıkan/aktif, zamanlı yayın, SEO, kopyalama, çöp kutusu |
| Ürün listesi | Ad/kod/marka araması, marka / kategori (alt kategoriler dahil) / aktif / öne çıkan / görsel filtreleri, toplu aktif-pasif-öne çıkar |
| Kategoriler | En fazla 3 seviye, döngü engeli, sürükle-bırak sıra, açıklama (SEO), görsel, menüde göster; silinen kategorinin alt kategorileri bir üste taşınır |
| Markalar | Logo, kapak, açıklama, web sitesi, ana sayfada göster, ürün sayısı, SEO |
| Ürün Özellikleri | Filtre özellikleri ve sıralanabilir değerleri (Renk → Beyaz, Gri …) |
| Excel / CSV | İçe aktar (.xlsx ve .csv; `;` veya `,` ayraç otomatik), dışa aktar (.xlsx / .csv), açıklamalı boş şablon |

**Aktarım kuralları:** eşleştirme `kod` ile yapılır; boş hücre mevcut değeri değiştirmez; hatalı satırlar raporlanır,
diğerleri kaydedilir; en fazla 5.000 satır; kuyruk/cron gerektirmez. Görseller ve PDF'ler dosyayla değil panelden yüklenir
(uzak adresten dosya indirmek güvenlik riski olduğu için bilerek eklenmedi).

**`App\Services\Catalog\ProductCatalog`:** Aşama 4'teki kategori sayfası için hazır sorgu katmanı — alt/ek kategoriler,
marka ve özellik filtreleri (aynı özellik içinde VEYA, özellikler arasında VE), arama, sıralama ve yalnızca sonuç
döndüren filtre seçenekleri.

## Aşama 4 — Ön yüz

| Adres | Sayfa |
|---|---|
| `/` | Ana sayfa — panelden yönetilen bloklar; `?onizleme=1` panel kullanıcısına taslağı gösterir |
| `/urunler`, `/urunler?q=` | Tüm ürünler ve arama sonuçları (filtreli) |
| `/kategori/{slug}` | Kategori: alt kategoriler, marka ve özellik filtreleri, sıralama, sayfalama, kategori açıklaması |
| `/urun/{slug}` | Ürün detayı: galeri (tam ekran), kod, teknik tablo, kullanım alanları, PDF'ler, WhatsApp, benzer ürünler |
| `/markalar`, `/marka/{slug}` | Marka listesi ve marka ürünleri |
| `/iletisim` | Site Ayarları'ndaki iletişim bilgileri, (varsa) yayındaki İletişim sayfası metni, tıklayınca yüklenen harita |
| `/{slug}` | Kurumsal ve yasal sayfalar (yalnızca yayındakiler; taslak panel kullanıcısına önizleme) |
| `/arama/oneriler` | Arama kutusu önerileri (JSON, dakikada 60 istek sınırı) |

- **Header:** iletişim şeridi, logo, öneri veren ürün araması, mega menü (Menü Yönetimi'nde "Mega menü" işaretli öğe), açılır alt menüler; mobilde çekmece menü ve iç içe kategori açılırları.
- **Footer:** logo, açıklama, sosyal medya, kategori sütunu, kurumsal/hızlı/yasal menüler, iletişim, telif yazısı.
- **SEO:** sayfa başına title/description, canonical, robots (filtreli/arama sayfaları ve önizleme `noindex`), Open Graph, Search Console/Bing doğrulama, JSON-LD (Organization, BreadcrumbList, Product — fiyat olmadığı için `offers` yok).
- **KVKK / çerez:** Analytics ve Tag Manager yalnızca çerez onayı verilirse yüklenir; Google Haritalar tıklanmadan yüklenmez; font sunucudan servis edilir (Google Fonts çağrısı yok).
- **Erişilebilirlik:** "İçeriğe geç" bağlantısı, klavye ile menü ve arama, ARIA etiketleri, `prefers-reduced-motion` desteği.
- **Performans:** derlenmiş CSS 17 KB, JS 30 KB (gzip); ilk slayt görseli öncelikli, diğer görseller lazy; menü, kategori ağacı ve ayarlar önbellekte.
- 375 px mobil genişlikte tüm sayfalarda yatay taşma olmadığı tarayıcıda doğrulandı.

**Demo verisi (yalnızca yerel):** `php artisan db:seed --class=DemoCatalogSeeder` "Demo" etiketli kategori, marka, ürün ve slaytlar oluşturur; production'da çalışmayı reddeder.

**Bilinen eksikler:** Görseller yüklendiği boyutta sunulur (otomatik küçültme/WebP dönüşümü yok — Aşama 6'da değerlendirilecek).
Lighthouse ölçümü bu ortamda yapılmadı; yayın öncesi Aşama 6'da test sunucusunda ölçülecek.

## Aşama 5 — Formlar ve teknik SEO

| Özellik | Ayrıntı |
|---|---|
| Teklif formu `/teklif-iste` | Ürün adı/kodu (`?urun=KOD` ile ürün sayfasından ön dolu), miktar, firma, ad soyad, telefon, e-posta, açıklama, KVKK onayı |
| İletişim formu `/iletisim` | Ad soyad, e-posta, telefon, konu, mesaj, KVKK onayı |
| Doğrulama | Form Request (sunucu tarafı), Türkçe hata mesajları, erişilebilir hata gösterimi |
| Spam koruması | Gizli honeypot alanı + şifreli zaman damgası (3 sn'den hızlı / 2 saatten eski gönderim reddedilir); botlara hata gösterilmez, kayıt sessizce atılır. Captcha yok. |
| Hız sınırı | IP başına dakikada 5, günde 30 form gönderimi |
| Kayıt | Veritabanına "Yeni" durumunda; KVKK onay zamanı, IP ve tarayıcı bilgisiyle; ürün kodu eşleşirse ürüne bağlanır |
| E-posta bildirimi | Teklifler "Teklif bildirim e-postası"na (yoksa iletişim e-postasına), mesajlar iletişim e-postasına; yanıtla → doğrudan talep sahibine. Yanıt döndükten sonra gönderilir; SMTP hatası kaydı kaybettirmez, log'a yazılır. Ziyaretçi metni e-postada HTML/Markdown olarak yorumlanmaz. |
| SMTP testi | Site Ayarları → "Test e-postası gönder" (SMTP bilgileri yalnızca `.env`'de) |
| `sitemap.xml` | Yayındaki sayfa, kategori, marka, ürün (+ ürün görseli); noindex ve başka adrese canonical verilmiş kayıtlar hariç; içerik kaydedilince otomatik yenilenir |
| `robots.txt` | Production: `/admin`, `/livewire`, `/arama/` kapalı + sitemap adresi. Diğer ortamlar: tamamen kapalı (test sunucusu dizine girmez) |
| 301/302 | Yalnızca 404 olan adreslerde uygulanır (canlı sayfayı gölgelemez), kullanım sayacı tutulur. Sayfa/kategori/marka/ürün slug'ı değişince eski adresten otomatik 301 oluşturulur; geri alma döngü yaratmaz, zincirler düzleştirilir. |

**Yayın notu:** Müşteriye otomatik "talebiniz alındı" e-postası bilerek eklenmedi — form üzerinden başkalarının adresine
e-posta gönderilmesine (spam aracı olarak kötüye kullanıma) yol açabilir. İstenirse yalnızca doğrulanmış alıcılarla eklenebilir.

## Dyson Professional kataloğu (`DysonCatalogSeeder`)

`php artisan db:seed --class=DysonCatalogSeeder` (ayrıca `DatabaseSeeder` içinde). Kaynak dosyalar `database/seeders/assets/dyson/`.

| Ana kategori | Alt kategori | Ürünler |
|---|---|---|
| Oto Bakım Sıvıları | AdBlue | Premium AdBlue 5 L, 10 L, 20 L, 1000 L IBC |
| | Antifriz | Antifriz -37 °C Mavi 3 L, Kırmızı 3 L, Antifriz 16 kg |
| | Oto Cam Suyu | Antifrizli Oto Cam Suyu 1 L, 3 L, 5 L |
| Bakım ve Temizlik Spreyleri | Yağlayıcı ve Pas Sökücü Spreyler | +40 Multi Sprey 200 ml, Pas Sökücü MoS2 400 ml, Sıvı Gres 400 ml |
| | Temizleyici Spreyler | Motor Temizleyici 500 ml, Fren Balata Temizleyici 500 ml |
| Yapıştırma ve Sızdırmazlık | Mum Silikon | Hot Melt Stick Ø7,4 × 300 mm (DYSN2594) |
| | Sıvı Contalar | RTV Nötr Sıvı Conta Siyah 200 ml |
| Endüstriyel Temizlik | El Temizleme Ürünleri | Çok Amaçlı El Temizleme Kremi 3 kg |

- Ürün metinleri ve teknik özellikler **ambalaj etiketlerinden** aktarıldı. Etikette yer alan sertifika/test ifadeleri
  (ISO, "dermatolojik olarak test edilmiştir", "Alman teknolojisi") belge ile doğrulanmadan siteye eklenmedi.
- Ürün kodları: etikette kod olan tek ürün `DYSN2594`. Diğerlerine geçici iç kod verildi (`DP-…`); gerçek kodlar panelden veya Excel ile güncellenmeli.
- Görseller şeffaf zeminli 1000×1000 WebP'ye çevrildi (ortalama ~60 KB). Dört sprey görselindeki opak beyaz zemin temizlendi.
- 3 ana sayfa slaytı (masaüstü 1920×700, mobil 800×1000 WebP) ürün görsellerinden üretildi; başlık ve buton metinleri panelden değiştirilebilir.
- Logo Site Ayarları'na (logo, koyu zemin logosu, favicon) ve markaya eklendi.
- Tek marka olduğu için ana sayfadaki "Markalarımız" bloğu pasif yapıldı; ikinci marka eklenince panelden açılabilir.
- Kategori görseli yüklenmemiş kategorilerin kartında, kategorideki öne çıkan ürünün görseli gösterilir.

## Cari bilgiler (`CompanyInfoSeeder`)

Firma cari bilgi formundaki bilgiler Site Ayarları'na işlendi (yalnızca boş alanlar doldurulur):
ünvan, yetkili ve muhasebe telefonları, info@ ve muhasebe@ e-postaları, merkez adres (ilçe, il, posta kodu),
vergi dairesi/numarası, MERSİS ve ticaret sicil numarası, web sitesi.

- Site Ayarları'na **Firma bilgileri** sekmesi ve telefon/e-posta etiket alanları eklendi.
- Footer: adres, etiketli telefon ve e-postalar, WhatsApp ve alt şeritte yasal bilgiler (panelden gizlenebilir).
- İletişim sayfası: tüm iletişim kanalları ve "Firma bilgileri" kutusu.
- Ana sayfa Organization JSON-LD: `legalName`, `taxID`, `PostalAddress`, `contactPoint`.
- MERSİS formda 15 hane (`733130534700001`) görünüyordu; Excel baştaki sıfırı sildiği için 16 haneli `0733130534700001` olarak kaydedildi.
- WhatsApp numarası olarak yetkili GSM (+90 530 422 17 28) kullanıldı.

## Kurumsal metinler (`CorporateContentSeeder`)

Hakkımızda, Vizyonumuz, Misyonumuz ve Kalite Politikamız yazıldı ve yayına alındı; ana sayfadaki "Firma tanıtım alanı"
Hakkımızda özetiyle dolduruldu. Metinler yalnızca bilinen bilgilere dayanır (ünvan, merkez, ürün grupları, hizmet verilen
sektörler, çalışma şekli); kuruluş yılı, sertifika, sayısal başarı veya "lider" gibi iddialar içermez (testle denetlenir).
Değerlerimiz, Neden Dyson Tedarik?, İletişim ve yasal metinler hâlâ taslak.

## Testler

`php artisan test` → **115 test, 436 doğrulama, tamamı geçti** (SQLite bellek içi).

- `tests/Feature/Frontend/InquiryFormsTest` — ön doldurma, kayıt + ürün bağlantısı + bildirim alıcısı/yanıt adresi, Türkçe doğrulama, honeypot ve zaman tuzağı, hız sınırı, iletişim formu, e-postada HTML/Markdown kaçışı, SMTP hatasında kaydın korunması
- `tests/Feature/Frontend/SeoInfrastructureTest` — sitemap içeriği ve otomatik yenilenme, robots.txt ortam ayrımı, yönlendirme kuralları ve sayaç, slug değişiminde otomatik 301 ve döngü koruması, SMTP test aksiyonu

- `tests/Feature/Frontend/FrontendPagesTest` — blok yayını/taslak önizleme, boş blokların atlanması, sayfa yayın durumu, menüde taslak bağlantının gizlenmesi, kategori filtreleri ve noindex/canonical, ürün detayı ve JSON-LD, pasif/zamanlı ürün 404, XSS kaçışı, marka sayfası, arama önerileri ve hız sınırı, SEO varsayılanları, iletişim, 404 sayfası Katalog testleri ayrıca
MySQL 8.4 test veritabanında da çalıştırıldı (13 test geçti).

- `tests/Feature/Admin/CatalogAdminTest` — tüm katalog ekranları, ürün oluşturma/düzenleme ve ilişkiler, kod benzersizliği, arama, alt kategori filtresi, kategori döngü engeli, içe/dışa aktarma aksiyonları
- `tests/Feature/Catalog/ProductImportExportTest` — CSV/XLSX aktarımı, boş hücre kuralı, satır hataları, eksik marka seçeneği, HTML temizliği, dışa aktar → geri içe aktar
- `tests/Feature/Catalog/ProductCatalogTest` — kategori kapsamı, filtre birleşimleri, arama, bozuk filtre değerleri, filtre seçenekleri

- `tests/Unit/HtmlSanitizerTest` — script, olay öznitelikleri, `javascript:` bağlantı, iframe temizliği
- `tests/Unit/BlockRegistryTest` — bilinmeyen/pasif blokların atlanması
- `tests/Unit/RedirectPathTest` — yönlendirme yolu normalizasyonu
- `tests/Feature/Admin/AdminAccessTest` — misafir yönlendirmesi, pasif kullanıcı, editör/yönetici için tüm ekranların açılması ve yetki sınırları
- `tests/Feature/Admin/HomepageManagerTest` — taslak kaydı yayını etkilemez, yayınla kopyalar, HTML temizlenir
- `tests/Feature/Admin/CorporatePageTest` — sayfa oluşturma, slug üretimi/benzersizliği, zamanlı yayın
- `tests/Feature/Admin/SettingsAndPermissionsTest` — ayar kaydı ve önbellek, Analytics alanına script girilememesi, footer sayfasının başka grup yazamaması, policy kuralları, taslak sayfaya giden menü bağlantısının gizlenmesi

## Bilinen eksikler / sonraki adımlar

- Medya kütüphanesi bağımsız çalışır; diğer formlardaki görsel alanları kendi yükleme alanını kullanır (kütüphaneden seçme yok).
- Ön yüz rotaları (`home`, `pages.show`, `products.index` …) henüz yok; menü öğeleri bu yüzden panelde "Hedef bulunamadı" gösterir. Aşama 4'te rotalar eklenince otomatik çözülür.
- E-posta bildirimi, form spam koruması ve rate limiting Aşama 5'te ön yüz formlarıyla birlikte gelecek.

## Yerel geliştirme

Gereksinimler Laragon ile kuruldu (`C:\laragon`): PHP 8.3.36, Composer 2.10, Node 22, MySQL 8.4.

```bash
cd dysontedarik
php artisan migrate --seed   # .env içinde ADMIN_EMAIL / ADMIN_PASSWORD gerekli
php artisan serve            # http://127.0.0.1:8000/admin
php artisan test
```

MySQL bu kurulumda servis olarak değil, elle başlatıldı. Bilgisayar yeniden başlatılınca Laragon'u açıp "Start All" deyin.
