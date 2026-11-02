@extends('layouts.app')

@php
    $contact = $site->group('contact');
    $phoneHref = fn ($phone) => 'tel:'.preg_replace('/[^0-9+]/', '', $phone);
@endphp

@section('content')
    <x-page-hero :title="$page?->title ?? 'İletişim'" :description="$page?->excerpt" :breadcrumbs="$seo->breadcrumbs" />

    <section class="container-site py-10 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[1fr_1.4fr] lg:gap-12">
            <div class="space-y-4">
                @if ($contact['address'] ?? null)
                    <div class="flex gap-4 rounded-xl border border-slate-200 p-5">
                        <x-heroicon-o-map-pin class="size-6 shrink-0 text-accent-600" aria-hidden="true" />
                        <div>
                            <h2 class="text-sm font-semibold">Adres</h2>
                            <address class="mt-1 text-sm not-italic text-slate-700">{{ $contact['address'] }}</address>
                            @if ($contact['maps_url'] ?? null)
                                <a href="{{ $contact['maps_url'] }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-medium text-accent-600 hover:underline">Yol tarifi al →</a>
                            @endif
                        </div>
                    </div>
                @endif
                @foreach (array_filter([$contact['phone'] ?? null, $contact['phone_secondary'] ?? null]) as $phone)
                    <a href="{{ $phoneHref($phone) }}" class="flex gap-4 rounded-xl border border-slate-200 p-5 hover:border-navy-200">
                        <x-heroicon-o-phone class="size-6 shrink-0 text-accent-600" aria-hidden="true" />
                        <span><span class="block text-sm font-semibold text-navy-950">Telefon</span><span class="text-sm text-slate-700">{{ $phone }}</span></span>
                    </a>
                @endforeach
                @if ($contact['email'] ?? null)
                    <a href="mailto:{{ $contact['email'] }}" class="flex gap-4 rounded-xl border border-slate-200 p-5 hover:border-navy-200">
                        <x-heroicon-o-envelope class="size-6 shrink-0 text-accent-600" aria-hidden="true" />
                        <span><span class="block text-sm font-semibold text-navy-950">E-posta</span><span class="text-sm break-all text-slate-700">{{ $contact['email'] }}</span></span>
                    </a>
                @endif
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex gap-4 rounded-xl border border-slate-200 p-5 hover:border-navy-200">
                        <span class="text-[#25d366]">{!! \App\Support\BrandIcons::svg('whatsapp', 'size-6') !!}</span>
                        <span><span class="block text-sm font-semibold text-navy-950">WhatsApp</span><span class="text-sm text-slate-700">{{ $contact['whatsapp'] }}</span></span>
                    </a>
                @endif
                @if ($contact['working_hours'] ?? null)
                    <div class="flex gap-4 rounded-xl border border-slate-200 p-5">
                        <x-heroicon-o-clock class="size-6 shrink-0 text-accent-600" aria-hidden="true" />
                        <span><span class="block text-sm font-semibold text-navy-950">Çalışma saatleri</span><span class="text-sm text-slate-700">{{ $contact['working_hours'] }}</span></span>
                    </div>
                @endif
                @if (! array_filter($contact))
                    <p class="text-sm text-slate-500">İletişim bilgileri yakında eklenecek.</p>
                @endif
            </div>

            <div class="space-y-8">
                @if ($page?->content)
                    <div class="prose-site">{!! $page->content !!}</div>
                @endif

                {{-- Harita yalnızca https://www.google.com/maps/embed? adresiyle (Site Ayarları'nda doğrulanır) ve tıklanınca yüklenir. --}}
                @if ($embed = $contact['maps_embed_url'] ?? null)
                    <div x-data="{ load: false }" class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                        <template x-if="load">
                            <iframe src="{{ $embed }}" class="absolute inset-0 size-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Konum haritası" allowfullscreen></iframe>
                        </template>
                        <button type="button" x-show="! load" @click="load = true" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-slate-600 hover:bg-slate-200/60">
                            <x-heroicon-o-map class="size-10 text-navy-400" aria-hidden="true" />
                            Haritayı göster
                            <span class="max-w-xs text-center text-xs text-slate-500">Harita Google tarafından sunulur; yüklendiğinde Google çerezleri kullanılabilir.</span>
                        </button>
                    </div>
                @endif

                @if (Route::has('contact.store'))
                    @include('partials.contact-form')
                @endif
            </div>
        </div>
    </section>
@endsection
