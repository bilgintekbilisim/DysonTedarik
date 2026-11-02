<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRegistry;
use App\Support\Seo;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(Page $page, BlockData $data): View
    {
        // Taslak sayfayı panel kullanıcısı önizleyebilir; ziyaretçi için 404.
        $preview = ! $page->isPublished() && Gate::allows('update', $page);

        abort_if($page->type === PageType::Home, 404);
        abort_unless($page->isPublished() || $preview, 404);

        $blocks = collect(BlockRegistry::renderable($page->blocks))
            ->reject(fn (array $block) => BlockRegistry::BLOCKS[$block['type']]['home_only'])
            ->map(fn (array $block) => ['type' => $block['type'], 'data' => $data->resolve($block['type'], $block['data'] ?? [])])
            ->filter(fn (array $block) => $block['data'] !== null)
            ->values();

        $seo = Seo::for($page, $page->cover_image)->crumb('Ana Sayfa', route('home'))->crumb($page->title);
        $seo->noindex = $seo->noindex || $preview;
        $seo->type = 'article';

        return view('pages.show', compact('page', 'blocks', 'seo', 'preview'));
    }
}
