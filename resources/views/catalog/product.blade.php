@extends('layouts.app')

@php
    $images = $product->images;
    $quoteUrl = Route::has('quote.create') ? route('quote.create', ['urun' => $product->code]) : null;
    $specs = collect($product->technical_specs ?? [])->filter(fn ($s) => filled($s['label'] ?? null) && filled($s['value'] ?? null));
    $basics = array_filter(['Ürün kodu' => $product->code, 'Marka' => $product->brand?->name, 'Renk' => $product->color, 'Hacim / ağırlık' => $product->volume, 'Ambalaj' => $product->packaging]);
    // Filtre özelliği, ürünün renk/hacim alanıyla aynı değeri taşıyorsa ikinci kez gösterilmez.
    $attributeGroups = $product->attributeValues
        ->groupBy(fn ($value) => $value->attribute->name)
        ->reject(fn ($values) => in_array($values->pluck('value')->implode(', '), $basics, true));
@endphp

@section('content')
    <div class="border-b border-slate-200 bg-slate-50">
        <div class="container-site py-4">
            <x-breadcrumbs :items="$seo->breadcrumbs" />
        </div>
    </div>

    <section class="container-site py-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
            {{-- Galeri --}}
            <div x-data="productGallery({ count: {{ max(1, $images->count()) }} })" @keydown.escape.window="close()" class="lg:sticky lg:top-6 lg:self-start">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    @forelse ($images as $i => $image)
                        <button type="button" x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif @click="open()"
                                class="block aspect-square w-full cursor-zoom-in" aria-label="Görseli büyüt">
                            <x-img :path="$image->path" :alt="$image->alt ?: $product->name" :eager="$i === 0" width="800" height="800" class="size-full object-contain p-6" />
                        </button>
                    @empty
                        <x-img :path="null" :alt="$product->name" class="aspect-square w-full" />
                    @endforelse

                    @if ($images->count() > 1)
                        <button type="button" @click="prev()" class="absolute start-3 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow hover:bg-white" aria-label="Önceki görsel">
                            <x-heroicon-m-chevron-left class="size-5" aria-hidden="true" />
                        </button>
                        <button type="button" @click="next()" class="absolute end-3 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow hover:bg-white" aria-label="Sonraki görsel">
                            <x-heroicon-m-chevron-right class="size-5" aria-hidden="true" />
                        </button>
                    @endif
                </div>

                @if ($images->count() > 1)
                    <ul class="no-scrollbar mt-3 flex gap-2 overflow-x-auto">
                        @foreach ($images as $i => $image)
                            <li class="shrink-0">
                                <button type="button" @click="show({{ $i }})" class="size-18 overflow-hidden rounded-lg border-2 bg-white sm:size-20"
                                        :class="current === {{ $i }} ? 'border-accent-500' : 'border-slate-200 hover:border-slate-300'"
                                        aria-label="{{ $i + 1 }}. görsel" :aria-current="current === {{ $i }}">
                                    <x-img :path="$image->path" alt="" width="80" height="80" class="size-full object-contain p-1" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Tam ekran görüntüleme --}}
                @if ($images->isNotEmpty())
                    <div x-show="zoomed" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/95 p-4" @click.self="close()" role="dialog" aria-modal="true" aria-label="Görsel">
                        <button type="button" @click="close()" class="absolute end-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Kapat">
                            <x-heroicon-o-x-mark class="size-6" aria-hidden="true" />
                        </button>
                        @foreach ($images as $i => $image)
                            <img x-show="current === {{ $i }}" x-cloak src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt ?: $product->name }}" loading="lazy" class="max-h-[90vh] max-w-full object-contain">
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Ürün bilgisi --}}
            <div>
                @if ($product->brand)
                    <a href="{{ route('brands.show', $product->brand) }}" class="eyebrow normal-case hover:underline">{{ $product->brand->name }}</a>
                @endif
                <h1 class="mt-2 text-2xl leading-tight sm:text-3xl lg:text-4xl">{{ $product->name }}</h1>
                <p class="mt-3 inline-flex items-center gap-2 rounded-md bg-navy-50 px-2.5 py-1 font-mono text-sm text-navy-900">
                    Ürün kodu: <span class="font-semibold">{{ $product->code }}</span>
                </p>

                @if ($product->short_description)
                    <p class="mt-5 text-base leading-7 text-slate-700">{{ $product->short_description }}</p>
                @endif

                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    @if ($quoteUrl)
                        <a href="{{ $quoteUrl }}" class="btn-primary flex-1 !py-3.5">
                            <x-heroicon-o-document-text class="size-5" aria-hidden="true" /> Teklif İste
                        </a>
                    @endif
                    @if ($whatsapp)
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-whatsapp flex-1 !py-3.5">
                            {!! \App\Support\BrandIcons::svg('whatsapp', 'size-5') !!} WhatsApp ile bilgi al
                        </a>
                    @endif
                    @if (! $quoteUrl && ! $whatsapp)
                        <a href="{{ route('contact') }}" class="btn-dark flex-1 !py-3.5">Bilgi almak için iletişime geçin</a>
                    @endif
                </div>

                <dl class="mt-8 divide-y divide-slate-200 rounded-xl border border-slate-200">
                    @foreach ($basics as $label => $value)
                        <div class="grid grid-cols-[9rem_1fr] gap-4 px-4 py-3 text-sm">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-navy-950">{{ $value }}</dd>
                        </div>
                    @endforeach
                    @foreach ($attributeGroups as $name => $values)
                        <div class="grid grid-cols-[9rem_1fr] gap-4 px-4 py-3 text-sm">
                            <dt class="text-slate-500">{{ $name }}</dt>
                            <dd class="font-medium text-navy-950">{{ $values->pluck('value')->implode(', ') }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($product->documents->isNotEmpty())
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold">Teknik dokümanlar</h2>
                        <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($product->documents as $document)
                                <li>
                                    <a href="{{ Storage::disk('public')->url($document->path) }}" target="_blank" rel="noopener"
                                       class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-navy-900 hover:border-navy-300 hover:bg-navy-50">
                                        <x-heroicon-o-document-arrow-down class="size-5 shrink-0 text-accent-600" aria-hidden="true" />
                                        <span class="flex-1">{{ $document->title }}</span>
                                        <span class="text-xs text-slate-400">PDF</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Detay sekmeleri --}}
    @php
        $tabs = array_filter([
            'aciklama' => $product->description ? 'Ürün açıklaması' : null,
            'ebatlar' => ! empty($product->variant_table['rows']) ? 'Ebatlar ve artikeller ('.count($product->variant_table['rows']).')' : null,
            'teknik' => $specs->isNotEmpty() ? 'Teknik özellikler' : null,
            'kullanim' => $product->usage_areas ? 'Kullanım alanları' : null,
        ]);
    @endphp
    @if ($tabs)
        <section class="border-t border-slate-200 bg-slate-50">
            <div class="container-site py-10 lg:py-14" x-data="{ tab: @js(array_key_first($tabs)) }">
                <div class="no-scrollbar flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <button type="button" role="tab" id="sekme-{{ $key }}" aria-controls="panel-{{ $key }}" @click="tab = '{{ $key }}'"
                                :aria-selected="tab === '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'border-accent-500 text-navy-950' : 'border-transparent text-slate-500 hover:text-navy-900'"
                                class="-mb-px shrink-0 border-b-2 px-4 py-3 text-sm font-semibold whitespace-nowrap">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="pt-8">
                    @isset($tabs['aciklama'])
                        <div id="panel-aciklama" role="tabpanel" aria-labelledby="sekme-aciklama" x-show="tab === 'aciklama'" class="prose-site max-w-4xl">{!! $product->description !!}</div>
                    @endisset
                    @isset($tabs['ebatlar'])
                        @php $table = $product->variant_table; @endphp
                        <div id="panel-ebatlar" role="tabpanel" aria-labelledby="sekme-ebatlar" x-show="tab === 'ebatlar'" @if (array_key_first($tabs) !== 'ebatlar') x-cloak @endif>
                            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table class="w-full min-w-[36rem] text-sm">
                                    <caption class="sr-only">{{ $product->name }} ebat ve artikel listesi</caption>
                                    <thead class="bg-navy-50 text-start">
                                        <tr>
                                            @foreach ($table['headers'] as $header)
                                                <th scope="col" class="px-4 py-3 text-start font-semibold whitespace-nowrap text-navy-900">{{ $header }}</th>
                                            @endforeach
                                            @if ($quoteUrl)
                                                <th scope="col" class="px-4 py-3"><span class="sr-only">Teklif</span></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($table['rows'] as $i => $row)
                                            <tr class="hover:bg-slate-50">
                                                @foreach ($row as $cell)
                                                    <td class="px-4 py-2.5 whitespace-nowrap text-slate-700">{{ $cell }}</td>
                                                @endforeach
                                                @if ($quoteUrl)
                                                    <td class="px-4 py-2.5 text-end">
                                                        <a href="{{ route('quote.create', ['urun' => $product->code, 'secenek' => $i]) }}" class="text-xs font-semibold whitespace-nowrap text-accent-600 hover:underline" rel="nofollow">Teklif iste</a>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endisset
                    @isset($tabs['teknik'])
                        <div id="panel-teknik" role="tabpanel" aria-labelledby="sekme-teknik" x-show="tab === 'teknik'" @if (array_key_first($tabs) !== 'teknik') x-cloak @endif class="max-w-3xl overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <table class="w-full text-sm">
                                <caption class="sr-only">{{ $product->name }} teknik özellikleri</caption>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($specs as $spec)
                                        <tr>
                                            <th scope="row" class="w-1/2 bg-slate-50 px-4 py-3 text-start font-medium text-slate-600">{{ $spec['label'] }}</th>
                                            <td class="px-4 py-3 font-medium text-navy-950">{{ $spec['value'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endisset
                    @isset($tabs['kullanim'])
                        <div id="panel-kullanim" role="tabpanel" aria-labelledby="sekme-kullanim" x-show="tab === 'kullanim'" @if (array_key_first($tabs) !== 'kullanim') x-cloak @endif class="prose-site max-w-4xl">{!! $product->usage_areas !!}</div>
                    @endisset
                </div>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="section">
            <div class="container-site">
                <x-section-heading title="Benzer ürünler" :link="$product->category ? route('categories.show', $product->category) : null" link-text="Kategoriye git" />
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($related->take(4) as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Mobilde alt sabit eylem çubuğu --}}
    @if ($quoteUrl || $whatsapp)
        <div class="fixed inset-x-0 bottom-0 z-30 flex gap-2 border-t border-slate-200 bg-white/95 p-3 backdrop-blur sm:hidden">
            @if ($quoteUrl)
                <a href="{{ $quoteUrl }}" class="btn-primary flex-1">Teklif İste</a>
            @endif
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-whatsapp flex-1">WhatsApp</a>
            @endif
        </div>
        @push('head')
            <style>@media (max-width: 639px) { body { padding-bottom: 4.5rem; } .wa-float { display: none; } }</style>
        @endpush
    @endif
@endsection
