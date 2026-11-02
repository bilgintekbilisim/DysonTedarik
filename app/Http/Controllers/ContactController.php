<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Page;
use App\Support\Seo;
use Illuminate\View\View;

/** İletişim bilgileri her zaman gösterilir; panelde "İletişim" sayfası yayındaysa onun metni ve SEO'su eklenir. */
class ContactController extends Controller
{
    public function __invoke(): View
    {
        $page = Page::query()->published()->where('type', PageType::Contact)->orderBy('sort_order')->first();

        $seo = ($page ? Seo::for($page, $page->cover_image) : new Seo(title: 'İletişim'))
            ->crumb('Ana Sayfa', route('home'))
            ->crumb($page?->title ?? 'İletişim');

        return view('contact', compact('page', 'seo'));
    }
}
