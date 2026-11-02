<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\SettingsService;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRegistry;
use App\Support\Company;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, BlockData $data, SettingsService $settings): View
    {
        $home = Page::home();

        // ?onizleme=1 yalnızca panel kullanıcısına taslağı gösterir; ziyaretçi her zaman yayındaki sürümü görür.
        $preview = $request->boolean('onizleme') && $home && Gate::allows('update', $home);
        $source = $preview ? $home->draft_blocks : $home?->blocks;

        $blocks = collect(BlockRegistry::renderable($source))
            ->map(fn (array $block) => ['type' => $block['type'], 'data' => $data->resolve($block['type'], $block['data'] ?? [])])
            ->filter(fn (array $block) => $block['data'] !== null)
            ->values();

        $seo = $home ? Seo::for($home) : new Seo;
        $seo->title = $home?->meta_title ?: $settings->get('seo.default_title', $settings->get('general.site_name'));
        $seo->noindex = $preview || (bool) $home?->noindex;
        $seo->canonical = $home?->canonical_url ?: route('home');
        $seo->addSchema(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings->get('general.company_title', $settings->get('general.site_name')),
            'url' => route('home'),
            'logo' => $settings->get('general.logo') ? url(asset('storage/'.$settings->get('general.logo'))) : null,
            'telephone' => $settings->get('contact.phone'),
            'email' => $settings->get('contact.email'),
            'sameAs' => array_values(array_filter($settings->group('social'))) ?: null,
            ...Company::schema(),
        ]));

        return view('home', compact('blocks', 'seo', 'preview'));
    }
}
