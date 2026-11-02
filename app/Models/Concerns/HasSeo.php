<?php

namespace App\Models\Concerns;

/**
 * meta_title, meta_description, og_image, canonical_url ve noindex sütunlarına sahip modeller.
 * Boş bırakılan SEO alanları için içerikten makul varsayılanlar üretir.
 */
trait HasSeo
{
    public function initializeHasSeo(): void
    {
        $this->mergeCasts(['noindex' => 'boolean']);
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: (string) ($this->title ?? $this->name);
    }

    public function seoDescription(): ?string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        $fallback = $this->excerpt ?? $this->short_description ?? $this->summary ?? $this->description ?? null;

        return $fallback ? str(strip_tags($fallback))->squish()->limit(160)->value() : null;
    }
}
