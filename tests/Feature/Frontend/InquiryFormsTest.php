<?php

namespace Tests\Feature\Frontend;

use App\Enums\InquiryStatus;
use App\Mail\InquiryReceived;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\SettingsService;
use App\Support\SpamGuard;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InquiryFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutDefer();
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class]);
        app(SettingsService::class)->setMany(['contact' => ['email' => 'info@ornek.test', 'quote_email' => 'satis@ornek.test']]);
        Mail::fake();
    }

    /** Form açıldıktan 10 saniye sonra gönderilmiş gibi geçerli zaman damgası. */
    private function protection(): array
    {
        return [SpamGuard::TIMESTAMP => Crypt::encryptString((string) now()->subSeconds(10)->timestamp), SpamGuard::HONEYPOT => ''];
    }

    private function quote(array $overrides = []): array
    {
        return [
            'product_name' => 'Nötr Silikon', 'product_code' => 'NS-1', 'quantity' => '20 koli',
            'company_name' => 'Örnek Yapı', 'full_name' => 'Ayşe Yılmaz', 'phone' => '0532 000 00 00',
            'email' => 'ayse@ornek.test', 'message' => "Satır 1\nSatır 2", 'kvkk' => '1',
            ...$this->protection(), ...$overrides,
        ];
    }

    #[Test]
    public function the_quote_page_prefills_the_product_from_its_code(): void
    {
        Product::factory()->create(['name' => 'Montaj Köpüğü', 'code' => 'MK-750']);

        $this->get('/teklif-iste?urun=MK-750')
            ->assertOk()
            ->assertSee('value="Montaj Köpüğü"', false)
            ->assertSee('value="MK-750"', false)
            ->assertSee('name="'.SpamGuard::HONEYPOT.'"', false)
            ->assertSee('<link rel="canonical" href="'.url('/teklif-iste').'">', false);
    }

    #[Test]
    public function a_valid_quote_is_stored_linked_to_the_product_and_notified(): void
    {
        $product = Product::factory()->create(['code' => 'NS-1']);

        $this->post('/teklif-iste', $this->quote())
            ->assertRedirect('/teklif-iste')
            ->assertSessionHas('form_success', 'quote');

        $quote = QuoteRequest::query()->sole();
        $this->assertSame($product->id, $quote->product_id);
        $this->assertSame(InquiryStatus::New, $quote->status);
        $this->assertNotNull($quote->consent_at);
        $this->assertSame('127.0.0.1', $quote->ip_address);

        Mail::assertSent(InquiryReceived::class, fn (InquiryReceived $mail) => $mail->hasTo('satis@ornek.test')
            && $mail->hasReplyTo('ayse@ornek.test')
            && $mail->inquiry->is($quote));

        $this->followingRedirects()->post('/teklif-iste', $this->quote())->assertSee('Teklif talebiniz alındı');
    }

    #[Test]
    public function quote_validation_errors_are_shown_in_turkish(): void
    {
        $this->from('/teklif-iste')
            ->post('/teklif-iste', $this->quote(['full_name' => '', 'phone' => 'abc', 'email' => 'gecersiz', 'kvkk' => null]))
            ->assertRedirect('/teklif-iste')
            ->assertSessionHasErrors(['full_name', 'phone', 'email', 'kvkk']);

        $this->followingRedirects()->from('/teklif-iste')
            ->post('/teklif-iste', $this->quote(['full_name' => '']))
            ->assertSee('Ad soyad alanı zorunludur.');

        $this->assertSame(0, QuoteRequest::query()->count());
        Mail::assertNothingSent();
    }

    #[Test]
    public function bots_are_silently_ignored(): void
    {
        $this->post('/teklif-iste', $this->quote([SpamGuard::HONEYPOT => 'http://spam.test']))->assertSessionHas('form_success');
        $this->post('/teklif-iste', $this->quote([SpamGuard::TIMESTAMP => Crypt::encryptString((string) now()->timestamp)]))->assertSessionHas('form_success');
        $this->post('/teklif-iste', $this->quote([SpamGuard::TIMESTAMP => 'sahte']))->assertSessionHas('form_success');

        $this->assertSame(0, QuoteRequest::query()->count());
        Mail::assertNothingSent();
    }

    #[Test]
    public function forms_are_rate_limited_per_ip(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/iletisim', ['full_name' => 'Ali Veli', 'email' => "a{$i}@ornek.test", 'message' => 'Merhaba, bilgi almak istiyorum.', 'kvkk' => '1', ...$this->protection()])
                ->assertRedirect();
        }

        $this->post('/iletisim', ['full_name' => 'Ali Veli', 'email' => 'x@ornek.test', 'message' => 'Merhaba, bilgi almak istiyorum.', 'kvkk' => '1', ...$this->protection()])
            ->assertStatus(429);

        $this->assertSame(5, ContactMessage::query()->count());
    }

    #[Test]
    public function the_contact_form_stores_messages_and_notifies_the_contact_address(): void
    {
        $this->get('/iletisim')->assertOk()->assertSee('Bize yazın')->assertSee('action="'.url('/iletisim').'"', false);

        $this->post('/iletisim', [
            'full_name' => 'Mehmet Demir', 'email' => 'mehmet@ornek.test', 'subject' => 'Bayilik',
            'message' => 'Bayilik koşulları hakkında bilgi alabilir miyim?', 'kvkk' => '1', ...$this->protection(),
        ])->assertRedirect(url('/iletisim').'#iletisim-formu');

        $this->assertSame('Bayilik', ContactMessage::query()->sole()->subject);
        Mail::assertSent(InquiryReceived::class, fn ($mail) => $mail->hasTo('info@ornek.test'));
    }

    #[Test]
    public function the_notification_email_does_not_render_visitor_html_or_markdown(): void
    {
        $quote = QuoteRequest::query()->create([
            'product_name' => 'Ürün', 'full_name' => '<b>Kalın</b>', 'phone' => '0500', 'email' => 'a@b.test',
            'message' => "[Tıklayın](https://kotu.test)\n<script>alert(1)</script>",
        ]);

        $html = (new InquiryReceived($quote))->render();

        $this->assertStringNotContainsString('href="https://kotu.test"', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>Kalın</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Kalın&lt;/b&gt;', $html);
    }

    #[Test]
    public function a_failing_mail_server_does_not_lose_the_inquiry(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP bağlantısı kurulamadı'));

        $this->post('/teklif-iste', $this->quote())->assertRedirect('/teklif-iste');

        $this->assertSame(1, QuoteRequest::query()->count());
    }
}
