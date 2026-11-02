{{-- JavaScript kapalıyken de çalışan arama formu; açıkken yazdıkça öneri listesi gösterir. --}}
<form action="{{ route('products.index') }}" method="get" role="search"
      x-data="searchBox({ endpoint: @js(route('search.suggest')) })"
      @submit="choose($event)" @click.outside="close()" class="relative w-full max-w-2xl">
    <label for="{{ $id }}" class="sr-only">Ürün adı, kodu veya marka ara</label>
    <div class="relative">
        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input id="{{ $id }}" name="q" type="search" autocomplete="off" value="{{ request()->routeIs('products.index') ? request('q') : '' }}"
               x-model="query" @input="onInput()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.escape="close()"
               role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="{{ $id }}-oneriler"
               placeholder="Ürün adı, kodu veya marka ara…" maxlength="100"
               class="h-11 w-full rounded-lg border border-slate-300 bg-slate-50 ps-11 pe-24 text-sm text-navy-950 placeholder:text-slate-400 focus:border-navy-400 focus:bg-white focus:ring-2 focus:ring-navy-100 focus:outline-none">
        <button type="submit" class="absolute end-1.5 top-1/2 -translate-y-1/2 rounded-md bg-navy-900 px-4 py-1.5 text-sm font-semibold text-white hover:bg-navy-800">Ara</button>
    </div>

    <div x-show="open" x-transition.opacity x-cloak id="{{ $id }}-oneriler" role="listbox"
         class="absolute inset-x-0 top-full z-40 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-navy-900/10">
        <template x-if="results.length === 0">
            <p class="px-4 py-3 text-sm text-slate-500">Eşleşen ürün bulunamadı. <span class="font-medium">Enter</span> ile tüm sonuçlarda arayın.</p>
        </template>
        <ul>
            <template x-for="(item, index) in results" :key="item.url">
                <li role="option" :aria-selected="active === index">
                    <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5" :class="active === index ? 'bg-navy-50' : 'hover:bg-slate-50'">
                        <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-white">
                            <img x-show="item.image" :src="item.image" alt="" class="size-full object-contain" loading="lazy">
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-navy-950" x-text="item.name"></span>
                            <span class="block truncate text-xs text-slate-500"><span x-text="item.code"></span><span x-show="item.brand"> · <span x-text="item.brand"></span></span></span>
                        </span>
                    </a>
                </li>
            </template>
        </ul>
        <button type="submit" x-show="results.length" class="block w-full border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-start text-sm font-semibold text-accent-600 hover:bg-slate-100">
            Tüm sonuçları göster →
        </button>
    </div>
</form>
