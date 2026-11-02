<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Site ayarlarını tek sorguda okur ve önbellekte tutar.
 * Anahtarlar nokta notasyonu kullanır: "contact.phone", "seo.default_title".
 */
class SettingsService
{
    public const CACHE_KEY = 'site_settings';

    private ?array $loaded = null;

    public function all(): array
    {
        return $this->loaded ??= Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Bir gruba ait ayarları ["phone" => ...] biçiminde döndürür. */
    public function group(string $group): array
    {
        $prefix = $group.'.';

        return collect($this->all())
            ->filter(fn ($value, string $key) => str_starts_with($key, $prefix))
            ->mapWithKeys(fn ($value, string $key) => [substr($key, strlen($prefix)) => $value])
            ->all();
    }

    /**
     * Gruplanmış ayarları kaydeder: ["contact" => ["phone" => "..."]] → "contact.phone".
     * Değerin kendisi dizi olabilir (ör. istatistik listesi); yalnızca ilk iki seviye anahtara dönüşür.
     *
     * @param  array<string, array<string, mixed>>  $groups
     */
    public function setMany(array $groups): void
    {
        foreach ($groups as $group => $fields) {
            foreach ((array) $fields as $field => $value) {
                Setting::query()->updateOrCreate(['key' => "{$group}.{$field}"], ['value' => $value]);
            }
        }

        $this->flush();
    }

    /** Ayarları settings formunun beklediği iç içe yapıya çevirir. */
    public function nested(): array
    {
        $nested = [];

        foreach ($this->all() as $key => $value) {
            Arr::set($nested, $key, $value);
        }

        return $nested;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }
}
