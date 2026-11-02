@php
    $accent = $variant === 'accent';
    $phone = app(\App\Services\SettingsService::class)->get('contact.phone');
    $buttonUrl = $data['button_url'] ?? null;

    // Teklif sayfası henüz yayında değilse buton iletişim sayfasına gider.
    if ($buttonUrl === '/teklif-iste' && ! Route::has('quote.create')) {
        $buttonUrl = route('contact');
    }
@endphp

<section class="{{ $accent ? 'bg-accent-500' : 'bg-navy-900' }} relative isolate overflow-hidden">
    <div class="absolute -end-24 -top-24 -z-10 size-96 rounded-full {{ $accent ? 'bg-white/15' : 'bg-accent-500/15' }} blur-3xl" aria-hidden="true"></div>
    <div class="container-site flex flex-col gap-8 py-14 lg:flex-row lg:items-center lg:justify-between lg:py-16">
        <div class="max-w-2xl">
            <h2 class="text-2xl sm:text-3xl {{ $accent ? '' : '!text-white' }}">{{ $data['title'] ?? 'Bizimle iletişime geçin' }}</h2>
            @if ($data['text'] ?? null)
                <p class="mt-3 text-base {{ $accent ? 'text-navy-900' : 'text-navy-100' }}">{{ $data['text'] }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            @if (($data['button_text'] ?? null) && $buttonUrl)
                <a href="{{ $buttonUrl }}" class="{{ $accent ? 'btn-dark' : 'btn-primary' }}">
                    {{ $data['button_text'] }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                </a>
            @endif
            @if (($data['show_phone'] ?? true) && $phone)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn {{ $accent ? 'border border-navy-950/20 text-navy-950 hover:bg-white/20' : 'border border-white/20 text-white hover:bg-white/10' }}">
                    <x-heroicon-o-phone class="size-4" aria-hidden="true" /> {{ $phone }}
                </a>
            @endif
        </div>
    </div>
</section>
