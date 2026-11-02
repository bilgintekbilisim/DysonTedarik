{{--
    Panelden yüklenen görsel. Görsel yoksa nötr bir yer tutucu çizer (kırık resim göstermez).
    eager: sayfanın ilk görünen (LCP) görseli için true.
--}}
@props(['path' => null, 'alt' => '', 'eager' => false, 'sizes' => null, 'width' => null, 'height' => null])

@if ($path)
    <img
        src="{{ Storage::disk('public')->url($path) }}"
        alt="{{ $alt }}"
        @if ($width) width="{{ $width }}" @endif
        @if ($height) height="{{ $height }}" @endif
        @if ($sizes) sizes="{{ $sizes }}" @endif
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        decoding="{{ $eager ? 'sync' : 'async' }}"
        @if ($eager) fetchpriority="high" @endif
        {{ $attributes }}
    >
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-slate-100 text-slate-300']) }} role="img" aria-label="{{ $alt ?: 'Görsel yok' }}">
        <x-heroicon-o-photo class="size-10" aria-hidden="true" />
    </div>
@endif
