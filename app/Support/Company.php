<?php

namespace App\Support;

use App\Services\SettingsService;

/** Site Ayarları'ndaki firma ve adres bilgilerini görünümler ve yapılandırılmış veri için biçimlendirir. */
class Company
{
    private static function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    /** "Ferhatpaşa Mah. 77 Sok. No: 24/1, 34888 Ataşehir / İstanbul" */
    public static function address(): ?string
    {
        $s = self::settings();
        $street = $s->get('contact.address');
        $locality = trim(implode(' ', array_filter([
            $s->get('contact.postal_code'),
            implode(' / ', array_filter([$s->get('contact.district'), $s->get('contact.city')])),
        ])));

        // Eski kayıtlarda ilçe/il adres satırının içinde olabilir; tekrar etme.
        if ($street && $locality && str_contains(mb_strtolower($street), mb_strtolower((string) $s->get('contact.district')))) {
            return $street;
        }

        return implode(', ', array_filter([$street, $locality])) ?: null;
    }

    /** @return array<string, string> görünen ad => değer (yalnızca dolu olanlar) */
    public static function legalLines(): array
    {
        $s = self::settings();

        return array_filter([
            'Ünvan' => $s->get('general.company_title'),
            'Vergi dairesi' => $s->get('company.tax_office'),
            'Vergi no' => $s->get('company.tax_number'),
            'MERSİS no' => $s->get('company.mersis'),
            'Ticaret sicil no' => $s->get('company.trade_registry'),
        ]);
    }

    /** @return array<int, array{label: ?string, number: string, href: string}> */
    public static function phones(): array
    {
        $s = self::settings();

        return collect([
            [$s->get('contact.phone_label'), $s->get('contact.phone')],
            [$s->get('contact.phone_secondary_label'), $s->get('contact.phone_secondary')],
        ])->filter(fn ($p) => filled($p[1]))
            ->map(fn ($p) => ['label' => $p[0], 'number' => $p[1], 'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $p[1])])
            ->values()->all();
    }

    /** @return array<int, array{label: ?string, address: string}> */
    public static function emails(): array
    {
        $s = self::settings();

        return collect([
            [null, $s->get('contact.email')],
            [$s->get('contact.email_secondary_label'), $s->get('contact.email_secondary')],
        ])->filter(fn ($e) => filled($e[1]))
            ->map(fn ($e) => ['label' => $e[0], 'address' => $e[1]])
            ->values()->all();
    }

    /** schema.org Organization için adres ve resmi bilgiler. */
    public static function schema(): array
    {
        $s = self::settings();

        return array_filter([
            'legalName' => $s->get('general.company_title'),
            'taxID' => $s->get('company.tax_number'),
            'address' => $s->get('contact.address') ? array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $s->get('contact.address'),
                'addressLocality' => $s->get('contact.district'),
                'addressRegion' => $s->get('contact.city'),
                'postalCode' => $s->get('contact.postal_code'),
                'addressCountry' => 'TR',
            ]) : null,
            'contactPoint' => collect(self::phones())->map(fn ($p) => array_filter([
                '@type' => 'ContactPoint',
                'telephone' => $p['number'],
                'contactType' => $p['label'] ? mb_strtolower($p['label']) : 'customer service',
                'areaServed' => 'TR',
                'availableLanguage' => 'Turkish',
            ]))->all() ?: null,
        ]);
    }
}
