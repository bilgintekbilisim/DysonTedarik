@php $carousel = $variant === 'carousel'; @endphp

<section class="section">
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" :subtitle="$data['subtitle'] ?? null" :link="route('products.index')" link-text="Tüm ürünler" />

        @if ($carousel)
            <div x-data="carousel" class="relative">
                <div x-ref="track" class="no-scrollbar -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    @foreach ($data['categories'] as $category)
                        <div class="w-64 shrink-0 snap-start">@include('blocks.partials.category-card')</div>
                    @endforeach
                </div>
                @include('blocks.partials.carousel-controls')
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($data['categories'] as $category)
                    @include('blocks.partials.category-card')
                @endforeach
            </div>
        @endif
    </div>
</section>
