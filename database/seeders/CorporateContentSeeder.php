<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Hakkımızda, Vizyon, Misyon ve Kalite Politikası metinleri ile ana sayfa tanıtım bloğu.
 * Metinler yalnızca doğrulanmış bilgilere dayanır (ünvan, merkez, ürün grupları, çalışma şekli);
 * kuruluş yılı, sertifika veya sayısal başarı iddiası içermez.
 * İçeriği dolu sayfalara dokunmaz; panelden yapılan düzenlemeler korunur.
 */
class CorporateContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $data) {
            $page = Page::query()->where('slug', $slug)->first();

            if (! $page || filled($page->content)) {
                continue;
            }

            $page->update([
                ...$data,
                'status' => ContentStatus::Published,
                'published_at' => $page->published_at ?? now(),
            ]);
        }

        $this->homepageIntro();
    }

    private function pages(): array
    {
        return [
            'hakkimizda' => [
                'excerpt' => 'Sanayi, otomotiv, inşaat ve atölyelerin ihtiyaç duyduğu bakım, temizlik, yapıştırma ve sızdırmazlık ürünlerini tek noktadan tedarik ediyoruz.',
                'meta_title' => 'Hakkımızda',
                'meta_description' => 'Dyson Tedarik; sanayi, otomotiv ve inşaat sektörlerine Dyson Professional bakım, temizlik, yapıştırma ve sızdırmazlık ürünleri tedarik eden İstanbul merkezli firmadır.',
                'content' => <<<'HTML'
<h2>Biz kimiz?</h2>
<p><strong>DYSON TEDARİK YAPI VE SAN. ÜRÜNLERİ TİC. A.Ş.</strong>, İstanbul Ataşehir merkezli olarak sanayi, otomotiv, inşaat ve atölye sektörlerinin günlük işleyişinde ihtiyaç duyduğu bakım, temizlik, yapıştırma ve sızdırmazlık ürünlerinin tedarikini yapar. Ürünlerimizi <strong>Dyson Professional</strong> markası altında sunuyoruz.</p>
<p>Amacımız; servis, atölye ve işletmelerin sık kullandığı sarf ve bakım ürünlerine, ihtiyaç duydukları ambalajda ve tek bir muhatapla ulaşabilmelerini sağlamaktır.</p>

<h2>Ürün gruplarımız</h2>
<ul>
<li><strong>Oto bakım sıvıları:</strong> SCR sistemli dizel araçlar için AdBlue, antifriz ve antifrizli oto cam suyu.</li>
<li><strong>Bakım ve temizlik spreyleri:</strong> çok amaçlı bakım spreyi, pas sökücü, sıvı gres, motor temizleyici ve fren balata temizleyici.</li>
<li><strong>Yapıştırma ve sızdırmazlık:</strong> sıcak silikon tabancaları için mum silikon ve yüksek sıcaklığa dayanıklı RTV sıvı conta.</li>
<li><strong>Endüstriyel temizlik:</strong> yağ, gres, kurum ve boya gibi ağır kirler için el temizleme kremi.</li>
</ul>
<p>Ürünlerimizi 200 ml'lik spreylerden 1000 litrelik IBC tanklara kadar farklı ambalaj seçenekleriyle sunuyoruz; böylece tekil kullanıcıdan filo ve tesis ölçeğine kadar farklı ihtiyaçlara yanıt verebiliyoruz.</p>

<h2>Kimlere hizmet veriyoruz?</h2>
<ul>
<li>Oto servisleri, bakım atölyeleri ve yedek parça satıcıları</li>
<li>Nakliye firmaları ve araç filosu işleten kurumlar</li>
<li>Sanayi tesisleri, imalathaneler ve matbaalar</li>
<li>İnşaat ve yapı firmaları</li>
<li>Hırdavat ve yapı market bayileri</li>
</ul>

<h2>Nasıl çalışıyoruz?</h2>
<p>Satışlarımızı kurumsal tedarik modeliyle yürütüyoruz. Fiyatlarımız ürün, miktar ve teslimat koşullarına göre talebe özel hazırlanır. Ürün sayfalarındaki <strong>Teklif İste</strong> formu, WhatsApp hattımız veya telefonla bize ulaşabilir; ürün kodu ve miktar bilgisini paylaşarak teklif sürecini hızlandırabilirsiniz.</p>
<p>Her ürünün teknik özelliklerini, kullanım alanlarını ve ambalaj bilgilerini ürün sayfalarında yayınlıyoruz. Doğru ürünü seçmekte tereddüt ettiğiniz durumlarda ekibimiz size yardımcı olur.</p>
HTML,
                'blocks' => [
                    [
                        'type' => 'features',
                        'data' => [
                            'is_active' => true, 'visibility' => 'all', 'variant' => 'cards', 'anchor' => null,
                            'title' => 'Bizimle çalışırken',
                            'subtitle' => null,
                            'items' => [
                                ['icon' => 'cube', 'title' => 'Geniş ambalaj seçeneği', 'text' => '200 ml spreyden 1000 L IBC tanka kadar ihtiyacınıza uygun ambalaj.'],
                                ['icon' => 'document-check', 'title' => 'Talebe özel teklif', 'text' => 'Ürün, miktar ve teslimat koşullarınıza göre hazırlanan fiyatlandırma.'],
                                ['icon' => 'wrench-screwdriver', 'title' => 'Ürün bilgisi', 'text' => 'Teknik özellik, kullanım alanı ve ambalaj bilgileri her ürün sayfasında.'],
                                ['icon' => 'chat-bubble-left-right', 'title' => 'Doğrudan iletişim', 'text' => 'Telefon, WhatsApp ve teklif formu ile tek muhatap.'],
                                ['icon' => 'building-storefront', 'title' => 'Tek noktadan tedarik', 'text' => 'Bakım, temizlik, yapıştırma ve sızdırmazlık ürünleri bir arada.'],
                                ['icon' => 'users', 'title' => 'Kurumsal çalışma', 'text' => 'Servis, atölye, filo ve sanayi işletmelerine yönelik tedarik modeli.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'contact_cta',
                        'data' => [
                            'is_active' => true, 'visibility' => 'all', 'variant' => 'dark', 'anchor' => null,
                            'title' => 'İhtiyacınız olan ürünler için teklif alın',
                            'text' => 'Ürün kodu ve miktar bilgisini iletin, size özel teklifimizi hazırlayalım.',
                            'button_text' => 'Teklif iste',
                            'button_url' => '/teklif-iste',
                            'show_phone' => true,
                        ],
                    ],
                ],
            ],

            'vizyonumuz' => [
                'excerpt' => 'Sanayi, otomotiv ve yapı sektörlerinde bakım ve yardımcı ürün tedarikinde güvenilir, erişilebilir ve kalıcı bir çözüm ortağı olmak.',
                'meta_title' => 'Vizyonumuz',
                'meta_description' => 'Dyson Tedarik vizyonu: sanayi, otomotiv ve yapı sektörlerinde bakım ve yardımcı ürün tedarikinde güvenilir ve kalıcı bir çözüm ortağı olmak.',
                'content' => <<<'HTML'
<p>Sanayi, otomotiv ve yapı sektörlerinde bakım, temizlik, yapıştırma ve sızdırmazlık ürünleri denildiğinde <strong>güvenilir, erişilebilir ve kalıcı bir çözüm ortağı</strong> olarak tercih edilen tedarikçi olmak.</p>

<h2>Bu vizyona ulaşmak için hedeflerimiz</h2>
<ul>
<li>Dyson Professional ürün ailesini, müşterilerimizin sahada karşılaştığı ihtiyaçlara göre geliştirmek ve genişletmek.</li>
<li>Her ölçekteki işletmenin ürünlerimize ihtiyaç duyduğu ambalaj ve miktarda kolayca ulaşabilmesini sağlamak.</li>
<li>Ürün bilgisini açık ve doğru biçimde paylaşarak müşterilerimizin doğru ürünü seçmesine yardımcı olmak.</li>
<li>Bayi ve iş ortaklarımızla uzun vadeli, karşılıklı güvene dayalı ilişkiler kurmak.</li>
<li>Çevreye ve insan sağlığına duyarlı ürün kullanımını teşvik etmek.</li>
</ul>
HTML,
            ],

            'misyonumuz' => [
                'excerpt' => 'Müşterilerimize ihtiyaç duydukları ürünü doğru ambalajda, açık bilgi ve şeffaf iletişimle, zamanında sunmak.',
                'meta_title' => 'Misyonumuz',
                'meta_description' => 'Dyson Tedarik misyonu: ihtiyaç duyulan bakım ve sanayi ürünlerini doğru ambalajda, açık ürün bilgisi ve şeffaf iletişimle zamanında sunmak.',
                'content' => <<<'HTML'
<p>Servis, atölye, filo ve sanayi işletmelerinin ihtiyaç duyduğu bakım, temizlik, yapıştırma ve sızdırmazlık ürünlerini; <strong>doğru ambalajda, açık ürün bilgisiyle ve şeffaf bir iletişimle, zamanında</strong> sunmak.</p>

<h2>Bu doğrultuda</h2>
<ul>
<li><strong>Doğru ürün:</strong> Müşterimizin uygulamasına uygun ürünü önermek, ihtiyaç dışı ürün satmamak.</li>
<li><strong>Doğru bilgi:</strong> Teknik özellikleri, kullanım koşullarını ve güvenlik uyarılarını açık ve eksiksiz paylaşmak.</li>
<li><strong>Şeffaf teklif:</strong> Fiyat, miktar ve teslimat koşullarını net biçimde belirten teklifler hazırlamak.</li>
<li><strong>Erişilebilirlik:</strong> Telefon, WhatsApp ve teklif formu ile kolay ulaşılabilir olmak; talepleri kayıt altına alıp takip etmek.</li>
<li><strong>Güvenilir tedarik:</strong> Ürünleri uygun koşullarda depolamak ve verdiğimiz sözü zamanında yerine getirmek.</li>
</ul>
HTML,
            ],

            'kalite-politikamiz' => [
                'excerpt' => 'Tedarik ettiğimiz her üründe mevzuata uygunluk, doğru bilgi, uygun depolama ve müşteri memnuniyetini esas alıyoruz.',
                'meta_title' => 'Kalite Politikamız',
                'meta_description' => 'Dyson Tedarik kalite politikası: mevzuata uygun ürün tedariki, doğru ürün bilgisi, uygun depolama, müşteri geri bildirimlerinin değerlendirilmesi ve sürekli iyileştirme.',
                'content' => <<<'HTML'
<p>DYSON TEDARİK YAPI VE SAN. ÜRÜNLERİ TİC. A.Ş. olarak tedarik ettiğimiz ürünlerin ve sunduğumuz hizmetin kalitesini, müşterilerimizle kurduğumuz güvenin temeli olarak görüyoruz. Bu doğrultuda aşağıdaki ilkeleri benimsiyoruz:</p>

<h2>İlkelerimiz</h2>
<ol>
<li><strong>Mevzuata uygunluk:</strong> Ürünlerimizi yürürlükteki yasal düzenlemelere ve ilgili ürün standartlarına uygun olarak tedarik etmek ve sunmak.</li>
<li><strong>Doğru ve eksiksiz bilgi:</strong> Ürün etiketlerinde, teknik bilgilerde ve güvenlik uyarılarında yer alan bilgileri müşterilerimize doğru biçimde aktarmak.</li>
<li><strong>Uygun depolama ve sevkiyat:</strong> Ürünleri üretici tarafından belirtilen sıcaklık ve saklama koşullarına uygun şekilde depolamak, raf ömrünü takip etmek ve güvenli biçimde sevk etmek.</li>
<li><strong>Müşteri odaklılık:</strong> Talep, öneri ve şikâyetleri kayıt altına almak, zamanında yanıtlamak ve çözüm sürecini takip etmek.</li>
<li><strong>Tedarikçi değerlendirmesi:</strong> Ürün aldığımız iş ortaklarını kalite, süreklilik ve güvenilirlik açısından düzenli olarak değerlendirmek.</li>
<li><strong>Sürekli iyileştirme:</strong> Müşteri geri bildirimlerinden ve kendi süreçlerimizden öğrenerek hizmet kalitemizi sürekli geliştirmek.</li>
<li><strong>Çalışan bilinci:</strong> Ekibimizin ürün bilgisi, güvenli kullanım ve müşteri iletişimi konularında bilgili ve bilinçli olmasını sağlamak.</li>
<li><strong>Çevre ve sağlık:</strong> Ürünlerin güvenli kullanımı ve ambalajların doğru bertarafı konusunda müşterilerimizi bilgilendirmek.</li>
</ol>

<p>Bu politika yönetimimiz tarafından benimsenmiş olup tüm çalışanlarımızca uygulanır ve düzenli aralıklarla gözden geçirilir.</p>
<p><strong>DYSON TEDARİK YAPI VE SAN. ÜRÜNLERİ TİC. A.Ş.</strong><br>Yönetim</p>
HTML,
            ],
        ];
    }

    /** Ana sayfadaki "Firma tanıtım alanı" bloğunu Hakkımızda özetiyle doldurup aktif eder (içerik boşsa). */
    private function homepageIntro(): void
    {
        $home = Page::home();

        if (! $home) {
            return;
        }

        $fill = fn (?array $blocks) => collect($blocks ?? [])->map(function (array $block) {
            if ($block['type'] !== 'about_intro' || filled($block['data']['content'] ?? null)) {
                return $block;
            }

            $block['data'] = [
                ...$block['data'],
                'is_active' => true,
                'eyebrow' => 'Hakkımızda',
                'title' => 'Sanayi, otomotiv ve atölyeler için tek noktadan tedarik',
                'content' => '<p>DYSON TEDARİK YAPI VE SAN. ÜRÜNLERİ TİC. A.Ş. olarak oto bakım sıvılarından bakım spreylerine, '
                    .'yapıştırma ve sızdırmazlık ürünlerinden endüstriyel temizliğe kadar geniş bir ürün yelpazesini Dyson Professional markasıyla sunuyoruz.</p>'
                    .'<p>200 ml spreyden 1000 L IBC tanka uzanan ambalaj seçenekleri ve talebe özel tekliflerle servis, filo ve sanayi işletmelerinin ihtiyacına yanıt veriyoruz.</p>',
                'button_text' => 'Bizi tanıyın',
                'button_url' => '/hakkimizda',
            ];

            return $block;
        })->all();

        $home->update(['blocks' => $fill($home->blocks), 'draft_blocks' => $fill($home->draft_blocks)]);
    }
}
