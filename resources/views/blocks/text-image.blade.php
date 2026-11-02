@php $imageLeft = $variant === 'image_left'; @endphp

<section class="section">
    <div class="container-site grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
        @if ($data['image'] ?? null)
            <div class="{{ $imageLeft ? '' : 'lg:order-2' }}">
                <x-img :path="$data['image']" :alt="$data['image_alt'] ?? ''" width="720" height="540"
                       class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl shadow-navy-900/10" />
            </div>
        @endif
        <div class="{{ ($data['image'] ?? null) ? '' : 'lg:col-span-2 max-w-3xl' }}">
            @if ($data['eyebrow'] ?? null)
                <p class="eyebrow mb-3">{{ $data['eyebrow'] }}</p>
            @endif
            @if ($data['title'] ?? null)
                <h2 class="text-2xl sm:text-3xl">{{ $data['title'] }}</h2>
            @endif
            @if ($data['content'] ?? null)
                {{-- İçerik kayıt sırasında HtmlSanitizer ile temizlenmiştir. --}}
                <div class="prose-site mt-5">{!! $data['content'] !!}</div>
            @endif
            @if (($data['button_text'] ?? null) && ($data['button_url'] ?? null))
                <a href="{{ $data['button_url'] }}" class="btn-dark mt-7">
                    {{ $data['button_text'] }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                </a>
            @endif
        </div>
    </div>
</section>
