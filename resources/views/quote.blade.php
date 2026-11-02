@extends('layouts.app')

@php
    $phone = $site->get('contact.phone');
@endphp

@section('content')
    <x-page-hero title="Teklif İste" description="Ürün ve miktar bilgisini iletin; size en kısa sürede dönüş yapalım. Fiyat bilgisi talebinize özel hazırlanır." :breadcrumbs="$seo->breadcrumbs" />

    <section class="container-site py-10 lg:py-14">
        <div class="grid gap-10 lg:grid-cols-[1.6fr_1fr]">
            <div class="relative">
                <x-form.status form="quote" success="Teklif talebiniz alındı. Ekibimiz en kısa sürede sizinle iletişime geçecek." />

                <form method="post" action="{{ route('quote.store') }}" class="space-y-8" novalidate>
                    <fieldset class="space-y-5">
                        <legend class="mb-1 text-lg font-semibold text-navy-950">Ürün bilgisi</legend>
                        @if ($product)
                            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <x-img :path="$product->coverImage?->path" :alt="$product->name" width="64" height="64" class="size-16 shrink-0 rounded-lg bg-white object-contain p-1" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-navy-950">{{ $product->name }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $product->code }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.field name="product_name" label="Ürün adı" :required="true" :value="$product?->name" maxlength="255" class="sm:col-span-2" />
                            <x-form.field name="product_code" label="Ürün kodu" :value="$product?->code" maxlength="100" />
                            <x-form.field name="quantity" label="Talep miktarı" maxlength="100" placeholder="Ör. 20 koli, 500 adet" />
                        </div>
                    </fieldset>

                    <fieldset class="space-y-5">
                        <legend class="mb-1 text-lg font-semibold text-navy-950">İletişim bilgileri</legend>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.field name="company_name" label="Firma adı" maxlength="200" autocomplete="organization" />
                            <x-form.field name="full_name" label="Ad soyad" :required="true" maxlength="100" autocomplete="name" />
                            <x-form.field name="phone" label="Telefon" type="tel" :required="true" maxlength="20" autocomplete="tel" placeholder="05xx xxx xx xx" />
                            <x-form.field name="email" label="E-posta" type="email" :required="true" maxlength="150" autocomplete="email" />
                        </div>
                        <x-form.field name="message" label="Açıklama" :rows="5" maxlength="3000" hint="Teslimat yeri, renk/ambalaj tercihi gibi ek bilgiler." />
                    </fieldset>

                    <x-form.protection />

                    <button type="submit" class="btn-primary w-full !py-3.5 sm:w-auto">
                        <x-heroicon-o-paper-airplane class="size-5" aria-hidden="true" /> Teklif talebini gönder
                    </button>
                </form>
            </div>

            <aside class="space-y-4 lg:pt-1">
                <div class="rounded-2xl bg-navy-950 p-6 text-navy-100">
                    <h2 class="text-lg !text-white">Hızlı iletişim</h2>
                    <p class="mt-2 text-sm">Acil talepleriniz için bize doğrudan ulaşabilirsiniz.</p>
                    <div class="mt-5 space-y-2">
                        @if ($whatsappUrl)
                            <a href="{{ \App\Support\WhatsApp::link($product ? "Merhaba, {$product->name} ({$product->code}) için teklif almak istiyorum." : 'Merhaba, teklif almak istiyorum.') }}" target="_blank" rel="noopener" class="btn-whatsapp w-full">
                                {!! \App\Support\BrandIcons::svg('whatsapp', 'size-4') !!} WhatsApp ile yazın
                            </a>
                        @endif
                        @if ($phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn w-full border border-white/20 text-white hover:bg-white/10">
                                <x-heroicon-o-phone class="size-4" aria-hidden="true" /> {{ $phone }}
                            </a>
                        @endif
                        @if (! $whatsappUrl && ! $phone)
                            <a href="{{ route('contact') }}" class="btn w-full border border-white/20 text-white hover:bg-white/10">İletişim sayfası</a>
                        @endif
                    </div>
                </div>
                <ul class="space-y-3 rounded-2xl border border-slate-200 p-6 text-sm text-slate-700">
                    <li class="flex gap-3"><x-heroicon-o-check-circle class="size-5 shrink-0 text-accent-600" aria-hidden="true" /> Ürün kodunu biliyorsanız eklemeniz talebinizi hızlandırır.</li>
                    <li class="flex gap-3"><x-heroicon-o-check-circle class="size-5 shrink-0 text-accent-600" aria-hidden="true" /> Birden fazla ürün için açıklama alanına liste yazabilirsiniz.</li>
                    <li class="flex gap-3"><x-heroicon-o-check-circle class="size-5 shrink-0 text-accent-600" aria-hidden="true" /> Bilgileriniz yalnızca talebinizin yanıtlanması için kullanılır.</li>
                </ul>
            </aside>
        </div>
    </section>
@endsection
