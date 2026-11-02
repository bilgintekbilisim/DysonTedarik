<section class="section border-y border-slate-100">
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" :subtitle="$data['subtitle'] ?? null" :link="route('brands.index')" link-text="Tüm markalar" />

        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
            @foreach ($data['brands'] as $brand)
                <li>
                    <a href="{{ route('brands.show', $brand) }}"
                       class="flex h-24 items-center justify-center rounded-xl border border-slate-200 bg-white p-5 transition hover:border-navy-200 hover:shadow-md {{ $variant === 'cards' ? 'h-32 flex-col gap-3' : '' }}"
                       title="{{ $brand->name }}">
                        @if ($brand->logo)
                            <x-img :path="$brand->logo" :alt="$brand->name" width="160" height="64"
                                   class="max-h-12 w-auto object-contain {{ $variant === 'logos' ? 'opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0' : '' }}" />
                        @else
                            <span class="text-center font-semibold text-navy-900">{{ $brand->name }}</span>
                        @endif
                        @if ($variant === 'cards' && $brand->logo)
                            <span class="text-xs font-medium text-slate-600">{{ $brand->name }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
