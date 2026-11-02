@php
    $ga4 = $site->get('integrations.ga4_id');
    $gtm = $site->get('integrations.gtm_id');
    $cookiePage = \App\Models\Page::query()->published()->where('slug', 'cerez-politikasi')->first(['id', 'slug', 'status', 'published_at']);
@endphp

{{-- Analytics tanımlı değilse yalnızca zorunlu çerez kullanılır; bildirim yine bilgilendirme amaçlı gösterilir. --}}
<div x-data="cookieConsent({ ga4: @js($ga4), gtm: @js($gtm) })" x-show="visible" x-cloak x-transition
     class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl shadow-navy-950/20 sm:inset-x-6 sm:bottom-6"
     role="dialog" aria-live="polite" aria-label="Çerez bildirimi">
    <p class="text-sm leading-6 text-slate-700">
        Sitemiz, çalışması için gerekli çerezleri kullanır.
        @if ($ga4 || $gtm)
            Onay verirseniz, ziyaret istatistiklerini ölçmek için analiz çerezleri de kullanılır.
        @endif
        @if ($cookiePage)
            Ayrıntılar için <a href="{{ route('pages.show', $cookiePage) }}" class="font-medium text-accent-600 underline">Çerez Politikası</a>'nı inceleyebilirsiniz.
        @endif
    </p>
    <div class="mt-4 flex flex-wrap justify-end gap-2">
        @if ($ga4 || $gtm)
            <button type="button" class="btn-outline !py-2" @click="reject()">Yalnızca zorunlu</button>
            <button type="button" class="btn-dark !py-2" @click="accept()">Kabul et</button>
        @else
            <button type="button" class="btn-dark !py-2" @click="reject()">Anladım</button>
        @endif
    </div>
</div>
