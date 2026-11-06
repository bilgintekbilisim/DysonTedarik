<?php

namespace App\Models;

use App\Services\MediaLibrary;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['disk', 'path', 'original_name', 'mime_type', 'size', 'alt', 'folder', 'uploaded_by'])]
class Media extends Model
{
    protected $table = 'media';

    protected static function booted(): void
    {
        static::saving(function (Media $media) {
            $disk = Storage::disk($media->disk ?: 'public');

            if ($media->isDirty('path') && $disk->exists($media->path)) {
                $media->mime_type = $disk->mimeType($media->path) ?: null;
                $media->size = $disk->size($media->path);
            }

            $media->uploaded_by ??= auth()->id();
        });

        // Kütüphaneden silinen dosya diskten ve kullanıldığı kayıtlardan da kaldırılır.
        static::deleted(fn (Media $media) => app(MediaLibrary::class)->purge($media->path));
    }

    public function url(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
