<?php

namespace App\Http\Controllers;

use App\Actions\SubmitInquiry;
use App\Enums\PageType;
use App\Http\Requests\StoreContactMessage;
use App\Models\Page;
use App\Support\Seo;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** İletişim bilgileri her zaman gösterilir; panelde "İletişim" sayfası yayındaysa onun metni ve SEO'su eklenir. */
class ContactController extends Controller
{
    public function show(): View
    {
        $page = Page::query()->published()->where('type', PageType::Contact)->orderBy('sort_order')->first();

        $seo = ($page ? Seo::for($page, $page->cover_image) : new Seo(title: 'İletişim'))
            ->crumb('Ana Sayfa', route('home'))
            ->crumb($page?->title ?? 'İletişim');

        return view('contact', compact('page', 'seo'));
    }

    public function store(StoreContactMessage $request, SubmitInquiry $submit): RedirectResponse
    {
        if (! SpamGuard::isSpam($request)) {
            $submit->contact($request->validated(), $request);
        }

        return redirect()->to(route('contact').'#iletisim-formu')->with('form_success', 'contact');
    }
}
