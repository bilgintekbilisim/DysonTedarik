<section class="section {{ $background }}">
    <div class="container-site">
        <x-section-heading
            :title="$data['title'] ?? null"
            :subtitle="$data['subtitle'] ?? null"
            :link="($data['button_url'] ?? null) ?: route('products.index')"
            :link-text="$data['button_text'] ?? null"
        />

        @if ($variant === 'carousel')
            <div x-data="carousel" class="relative">
                <div x-ref="track" class="no-scrollbar -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                    @foreach ($data['products'] as $product)
                        <x-product-card :product="$product" class="w-[46%] shrink-0 snap-start sm:w-64" />
                    @endforeach
                </div>
                @include('blocks.partials.carousel-controls')
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($data['products'] as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</section>
