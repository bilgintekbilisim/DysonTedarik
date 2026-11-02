{{--
    Blokları sırayla çizer. Her blok kendi şablonunu kullanır; "visibility" ile
    masaüstü/mobil görünürlüğü, "anchor" ile sayfa içi bağlantı kimliği uygulanır.
--}}
@foreach ($blocks as $index => $block)
    @php
        $data = $block['data'];
        $visibility = match ($data['visibility'] ?? 'all') {
            'desktop' => 'hidden lg:block',
            'mobile' => 'lg:hidden',
            default => '',
        };
    @endphp
    <div @if ($data['anchor'] ?? null) id="{{ $data['anchor'] }}" @endif class="{{ $visibility }}" data-block="{{ $block['type'] }}">
        @include(\App\Support\Blocks\BlockRegistry::view($block['type']), ['data' => $data, 'variant' => $data['variant'] ?? null, 'first' => $index === 0])
    </div>
@endforeach
