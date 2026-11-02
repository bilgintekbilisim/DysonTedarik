# Dyson Tedarik — İlerleme

Son güncelleme: 2026-11-02

## Aşama durumu

| Aşama | Durum |
|---|---|
| 1 — Temel altyapı (Laravel 13.39, Filament 5.11, MySQL 8.4, giriş, roller) | ✅ Tamamlandı |
| 2 — CMS (ana sayfa blokları, kurumsal sayfalar, menü, footer, site ayarları) | ✅ Panel tarafı tamamlandı |
| 3 — Ürün kataloğu | ⏳ Veri modeli ve modeller hazır; panel ekranları yapılmadı |
| 4 — Ön yüz (Blade + Tailwind 4 + Alpine) | ⏳ Başlanmadı (`/` hâlâ Laravel karşılama sayfası) |
| 5 — SEO ve iletişim (form gönderimi, SMTP, sitemap, yönlendirme middleware'i) | ⏳ Panel ekranları hazır, ön yüz akışı yok |
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

## Testler

`php artisan test` → **56 test, 144 doğrulama, tamamı geçti** (SQLite bellek içi).

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
