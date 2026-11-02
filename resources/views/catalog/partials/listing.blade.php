{{--
    Ürün listesi + filtre paneli (kategori ve tüm ürünler sayfası ortak).
    Filtreler GET formudur; JS açıkken seçim değişince form kendiliğinden gönderilir.
--}}
@php
    $selectedBrands = array_map('strval', $filters['marka']);
    $selectedAttributes = $filters['ozellik'];
    $activeCount = count($selectedBrands) + collect($selectedAttributes)->flatten()->count();
@endphp

<div x-data="{ filtersOpen: false }" class="container-site py-8 lg:py-12">
    <div class="lg:grid lg:grid-cols-[17rem_1fr] lg:gap-10">
        {{-- Filtreler --}}
        <aside>
            <div x-show="filtersOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-navy-950/60 lg:hidden" @click="filtersOpen = false"></div>
            <form method="get" x-ref="filters" @change="$refs.filters.submit()"
                  :class="filtersOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                  class="fixed inset-y-0 start-0 z-50 w-80 max-w-[85%] -translate-x-full overflow-y-auto bg-white p-5 shadow-2xl transition lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:p-0 lg:shadow-none"
                  aria-label="Ürün filtreleri">
                @if ($filters['q'])
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                @endif
                <input type="hidden" name="sirala" value="{{ $filters['sirala'] }}">

                <div class="mb-5 flex items-center justify-between lg:hidden">
                    <h2 class="text-lg">Filtreler</h2>
                    <button type="button" @click="filtersOpen = false" class="rounded-lg p-2" aria-label="Filtreleri kapat">
                        <x-heroicon-o-x-mark class="size-6" aria-hidden="true" />
                    </button>
                </div>

                @if ($facets['children']->isNotEmpty())
                    <section class="border-b border-slate-200 pb-5">
                        <h2 class="text-sm font-semibold">{{ isset($category) ? 'Alt kategoriler' : 'Kategoriler' }}</h2>
                        <ul class="mt-3 space-y-1">
                            @foreach ($facets['children'] as $child)
                                <li><a href="{{ route('categories.show', $child) }}" class="block rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-navy-50 hover:text-navy-950">{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($facets['brands']->isNotEmpty())
                    <fieldset x-data="{ more: false }" class="border-b border-slate-200 py-5">
                        <legend class="text-sm font-semibold text-navy-950">Marka</legend>
                        <div class="mt-3 space-y-2">
                            @foreach ($facets['brands'] as $brand)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700" @if ($loop->index >= 8) x-show="more" x-cloak @endif>
                                    <input type="checkbox" name="marka[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $selectedBrands, true))
                                           class="size-4 rounded border-slate-300 accent-navy-900">
                                    <span class="flex-1">{{ $brand->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $brand->products_count }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if ($facets['brands']->count() > 8)
                            <button type="button" @click="more = !more" class="mt-2 text-sm font-medium text-accent-600" x-text="more ? 'Daha az göster' : 'Tümünü göster'">Tümünü göster</button>
                        @endif
                    </fieldset>
                @endif

                @foreach ($facets['attributes'] as $attribute)
                    <fieldset x-data="{ more: false }" class="border-b border-slate-200 py-5">
                        <legend class="text-sm font-semibold text-navy-950">{{ $attribute->name }}@if ($attribute->unit) <span class="font-normal text-slate-400">({{ $attribute->unit }})</span>@endif</legend>
                        <div class="mt-3 space-y-2">
                            @foreach ($attribute->values as $value)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700" @if ($loop->index >= 8) x-show="more" x-cloak @endif>
                                    <input type="checkbox" name="ozellik[{{ $attribute->slug }}][]" value="{{ $value->slug }}"
                                           @checked(in_array($value->slug, (array) ($selectedAttributes[$attribute->slug] ?? []), true))
                                           class="size-4 rounded border-slate-300 accent-navy-900">
                                    <span class="flex-1">{{ $value->value }}</span>
                                    <span class="text-xs text-slate-400">{{ $value->products_count }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if ($attribute->values->count() > 8)
                            <button type="button" @click="more = !more" class="mt-2 text-sm font-medium text-accent-600" x-text="more ? 'Daha az göster' : 'Tümünü göster'">Tümünü göster</button>
                        @endif
                    </fieldset>
                @endforeach

                <div class="flex gap-2 pt-5">
                    <noscript><button type="submit" class="btn-dark flex-1">Uygula</button></noscript>
                    @if ($activeCount)
                        <a href="{{ url()->current().($filters['q'] ? '?q='.urlencode($filters['q']) : '') }}" class="btn-outline flex-1">Filtreleri temizle</a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- Sonuçlar --}}
        <div class="mt-0 min-w-0">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-600" aria-live="polite">
                    <span class="font-semibold text-navy-950">{{ $products->total() }}</span> ürün
                    @if ($filters['q']) — "<span class="font-medium">{{ $filters['q'] }}</span>" için @endif
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="filtersOpen = true" class="btn-outline !px-4 !py-2 lg:hidden">
                        <x-heroicon-o-adjustments-horizontal class="size-4" aria-hidden="true" /> Filtrele
                        @if ($activeCount)<span class="rounded-full bg-accent-500 px-1.5 text-xs text-navy-950">{{ $activeCount }}</span>@endif
                    </button>
                    <form method="get" x-data @change="$el.submit()">
                        @foreach (request()->except(['sirala', 'page']) as $key => $value)
                            @foreach ((array) $value as $subKey => $subValue)
                                @if (is_array($subValue))
                                    @foreach ($subValue as $item)
                                        <input type="hidden" name="{{ $key }}[{{ $subKey }}][]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $subValue }}">
                                @endif
                            @endforeach
                        @endforeach
                        <label for="sirala" class="sr-only">Sıralama</label>
                        <select id="sirala" name="sirala" class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-navy-950">
                            @foreach (\App\Services\Catalog\ProductCatalog::SORTS as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sirala'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="btn-dark !py-2">Sırala</button></noscript>
                    </form>
                </div>
            </div>

            @if ($products->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center">
                    <x-heroicon-o-magnifying-glass class="mx-auto size-10 text-slate-300" aria-hidden="true" />
                    <p class="mt-4 font-semibold text-navy-950">Ürün bulunamadı</p>
                    <p class="mt-1 text-sm text-slate-600">Arama terimini veya filtreleri değiştirmeyi deneyin.</p>
                    @if ($whatsapp = \App\Support\WhatsApp::link('Merhaba, aradığım ürün hakkında bilgi almak istiyorum: '.($filters['q'] ?: '')))
                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn-whatsapp mt-6">Aradığınızı bize sorun</a>
                    @endif
                </div>
            @else
                <h2 class="sr-only">Ürün listesi</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" :eager="$loop->index < 4" />
                    @endforeach
                </div>
                {{ $products->links() }}
            @endif
        </div>
    </div>
</div>
