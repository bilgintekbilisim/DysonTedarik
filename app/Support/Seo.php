<?php

namespace App\Support;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;

/**
 * Bir sayfanın <head> SEO verisi ve breadcrumb'ı. Controller doldurur, layout çizer.
 * Boş alanlar Site Ayarları'ndaki varsayılan SEO değerlerine düşer.
 */
class Seo
{
    /** @var array<int, array{label: string, url: ?string}> */
    public array $breadcrumbs = [];

    /** @var array<int, array> ek JSON-LD nesneleri */
    public array $schema = [];

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $canonical = null,
        public bool $noindex = false,
        public string $type = 'website',
    ) {}

    /** HasSeo kullanan modellerden (sayfa, ürün, kategori, marka) SEO verisi. */
    public static function for(object $model, ?string $fallbackImage = null): self
    {
        return new self(
            title: $model->seoTitle(),
            description: $model->seoDescription(),
            image: $model->og_image ?: $fallbackImage,
            canonical: $model->canonical_url ?: null,
            noindex: (bool) $model->noindex,
        );
    }

    public function crumb(string $label, ?string $url = null): self
    {
        $this->breadcrumbs[] = ['label' => $label, 'url' => $url];

        return $this;
    }

    public function addSchema(array $schema): self
    {
        $this->schema[] = $schema;

        return $this;
    }

    public function fullTitle(): string
    {
        $settings = app(SettingsService::class);
        $siteName = $settings->get('general.site_name', config('app.name'));
        $title = $this->title ?: $settings->get('seo.default_title', $siteName);

        $suffix = (string) $settings->get('seo.title_suffix', '');

        return ($suffix !== '' && ! str_contains($title, trim($suffix, ' |-'))) ? $title.$suffix : $title;
    }

    public function metaDescription(): ?string
    {
        return $this->description ?: app(SettingsService::class)->get('seo.default_description');
    }

    public function imageUrl(): ?string
    {
        $image = $this->image ?: app(SettingsService::class)->get('seo.default_og_image');

        if (! $image) {
            return null;
        }

        return str_starts_with($image, 'http') ? $image : url(Storage::disk('public')->url($image));
    }

    /** Sorgu parametresiz geçerli adres; sayfalama parametresi korunur. */
    public function canonicalUrl(): string
    {
        if ($this->canonical) {
            return $this->canonical;
        }

        $page = (int) request()->query('sayfa', request()->query('page', 1));

        return url()->current().($page > 1 ? '?page='.$page : '');
    }

    /** @return array<int, array> */
    public function jsonLd(): array
    {
        $items = $this->schema;

        if (count($this->breadcrumbs) > 1) {
            $items[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect($this->breadcrumbs)->values()->map(fn (array $crumb, int $i) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['label'],
                    'item' => $crumb['url'] ?? $this->canonicalUrl(),
                ]))->all(),
            ];
        }

        return $items;
    }
}
