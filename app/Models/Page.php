<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type', 'title', 'slug', 'excerpt', 'cover_image', 'content', 'blocks', 'draft_blocks',
    'status', 'show_in_menu', 'published_at', 'sort_order',
    'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
])]
class Page extends Model
{
    use HasFactory, HasSeo, HasSlug, SoftDeletes;

    protected string $slugSource = 'title';

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => ContentStatus::class,
            'blocks' => 'array',
            'draft_blocks' => 'array',
            'show_in_menu' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            $sanitizer = app(HtmlSanitizer::class);

            $page->content = $sanitizer->clean($page->content);
            $page->blocks = $sanitizer->cleanBlocks($page->blocks);
            $page->draft_blocks = $sanitizer->cleanBlocks($page->draft_blocks);

            if (auth()->check()) {
                $page->updated_by = auth()->id();
            }
        });
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    #[Scope]
    protected function corporate(Builder $query): void
    {
        $query->where('type', '!=', PageType::Home);
    }

    public static function home(): ?self
    {
        return static::query()->where('type', PageType::Home)->first();
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /** Taslağı yayındaki bloklara kopyalar (ana sayfa yayın akışı). */
    public function publishDraft(): void
    {
        $this->blocks = $this->draft_blocks;
        $this->status = ContentStatus::Published;
        $this->published_at = now();
        $this->save();
    }

    public function hasUnpublishedChanges(): bool
    {
        return json_encode($this->draft_blocks) !== json_encode($this->blocks);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
