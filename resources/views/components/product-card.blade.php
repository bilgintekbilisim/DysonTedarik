@props(['product', 'eager' => false])

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-navy-200 hover:shadow-lg hover:shadow-navy-900/5']) }}>
    <div class="relative aspect-square overflow-hidden bg-slate-50">
        <x-img
            :path="$product->coverImage?->path"
            :alt="$product->coverImage?->alt ?: $product->name"
            :eager="$eager"
            width="400"
            height="400"
            sizes="(min-width: 1280px) 280px, (min-width: 640px) 33vw, 50vw"
            class="size-full object-contain p-4 transition duration-300 group-hover:scale-105"
        />
        @if ($product->is_featured)
            <span class="absolute start-3 top-3 rounded-full bg-accent-500 px-2.5 py-1 text-[11px] font-semibold text-navy-950">Öne çıkan</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-1.5 p-4">
        @if ($product->brand)
            {{-- Marka adı yazıldığı gibi gösterilir: lang="tr" ile büyük harfe çevirmek "i"yi "İ" yapar. --}}
            <p class="text-xs font-semibold tracking-wide text-accent-600">{{ $product->brand->name }}</p>
        @endif
        <h3 class="line-clamp-2 text-sm leading-snug font-semibold text-navy-950 sm:text-base">
            <a href="{{ route('products.show', $product) }}" class="after:absolute after:inset-0 focus:outline-none">
                {{ $product->name }}
            </a>
        </h3>
        <p class="mt-auto pt-2 font-mono text-xs text-slate-500">Kod: {{ $product->code }}</p>
    </div>
</article>
