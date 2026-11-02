<section class="section">
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" />
        <div class="grid gap-5 {{ $variant === 'two_column' ? 'md:grid-cols-2' : '' }}">
            @foreach ($data['slides'] as $banner)
                @php $tag = $banner->button_url ? 'a' : 'div'; @endphp
                <{{ $tag }} @if ($banner->button_url) href="{{ $banner->button_url }}" @endif
                    class="group relative isolate flex min-h-56 items-end overflow-hidden rounded-2xl bg-navy-900 sm:min-h-72">
                    <picture>
                        @if ($banner->image_mobile)
                            <source media="(max-width: 767px)" srcset="{{ Storage::disk('public')->url($banner->image_mobile) }}">
                        @endif
                        <img src="{{ Storage::disk('public')->url($banner->image_desktop) }}" alt="{{ $banner->image_alt ?: '' }}" loading="lazy" decoding="async"
                             width="960" height="420" class="absolute inset-0 -z-10 size-full object-cover transition duration-500 group-hover:scale-105">
                    </picture>
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-950/90 via-navy-950/30 to-transparent" aria-hidden="true"></div>
                    <div class="p-6 sm:p-8">
                        <h3 class="text-xl !text-white sm:text-2xl">{{ $banner->title }}</h3>
                        @if ($banner->description)
                            <p class="mt-2 max-w-md text-sm text-navy-100">{{ $banner->description }}</p>
                        @endif
                        @if ($banner->button_text)
                            <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-400 group-hover:underline">
                                {{ $banner->button_text }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                            </span>
                        @endif
                    </div>
                </{{ $tag }}>
            @endforeach
        </div>
    </div>
</section>
