<a href="{{ route('categories.show', $category) }}" class="group relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-xl bg-navy-900 sm:aspect-[4/3]">
    @if ($category->image)
        <x-img :path="$category->image" :alt="$category->name" width="480" height="360"
               class="absolute inset-0 size-full object-cover opacity-80 transition duration-500 group-hover:scale-105 group-hover:opacity-90" />
    @else
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_30%,rgba(94,124,185,0.45),transparent_60%)]" aria-hidden="true"></div>
        @if ($category->fallback_image)
            {{-- Kategori görseli yoksa kategorideki öne çıkan ürünün görseli. --}}
            <x-img :path="$category->fallback_image" alt="" width="360" height="360"
                   class="absolute end-[-6%] top-[4%] h-[78%] w-auto object-contain drop-shadow-2xl transition duration-500 group-hover:scale-105 sm:end-[2%] sm:h-[88%]" />
        @endif
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-navy-950/95 via-navy-950/30 to-transparent" aria-hidden="true"></div>
    <div class="relative p-4 sm:p-5">
        <h3 class="text-base !text-white sm:text-lg">{{ $category->name }}</h3>
        <p class="mt-1 flex items-center gap-1 text-xs font-medium text-navy-100 sm:text-sm">
            @if ($category->primary_products_count)
                {{ $category->primary_products_count }} ürün ·
            @endif
            <span class="text-accent-400 group-hover:underline">İncele</span>
        </p>
    </div>
</a>
