<?php

namespace App\Services\Catalog;

class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $createdBrands = 0;

    public int $createdCategories = 0;

    /** @var array<int, string> satır numarası (dosyadaki, başlık = 1) => hata */
    public array $errors = [];

    public function failed(): int
    {
        return count($this->errors);
    }

    public function summary(): string
    {
        $parts = ["{$this->created} yeni", "{$this->updated} güncellenen"];

        if ($this->failed()) {
            $parts[] = "{$this->failed()} hatalı satır";
        }

        if ($this->createdBrands) {
            $parts[] = "{$this->createdBrands} yeni marka";
        }

        if ($this->createdCategories) {
            $parts[] = "{$this->createdCategories} yeni kategori";
        }

        return implode(', ', $parts).'.';
    }
}
