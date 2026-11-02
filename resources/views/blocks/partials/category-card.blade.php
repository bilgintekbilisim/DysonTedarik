<a href="{{ route('categories.show', $category) }}" class="group relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-xl bg-navy-900 sm:aspect-[4/3]">
    @if ($category->image)
        <x-img :path="$category->image" :alt="$category->name" width="480" height="360"
               class="absolute inset-0 size-full object-cover opacity-80 transition duration-500 group-hover:scale-105 group-hover:opacity-90" />
    @else
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(249,115,22,0.35),transparent_55%)]" aria-hidden="true"></div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-navy-950/95 via-navy-950/40 to-transparent" aria-hidden="true"></div>
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
