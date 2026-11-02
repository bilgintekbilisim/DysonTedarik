<?php

namespace App\Models;

use App\Services\RedirectResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['from_path', 'to_url', 'status_code', 'is_active'])]
class Redirect extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect) {
            $redirect->from_path = static::normalizePath($redirect->from_path);
        });

        static::saved(fn () => RedirectResolver::flush());
        static::deleted(fn () => RedirectResolver::flush());
    }

    /** "https://site.com/Eski-Sayfa/?a=1" → "/eski-sayfa" */
    public static function normalizePath(string $path): string
    {
        $path = parse_url(trim($path), PHP_URL_PATH) ?: '/';

        return '/'.trim(mb_strtolower(rawurldecode($path)), '/');
    }
}
