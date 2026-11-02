{{--
    Panelden yüklenen görsel. Görsel yoksa nötr bir yer tutucu çizer (kırık resim göstermez).
    eager: sayfanın ilk görünen (LCP) görseli için true.
    thumb: 480 px küçük görsel varsa srcset ile sunulur (ürün kartları).
--}}
@props(['path' => null, 'alt' => '', 'eager' => false, 'sizes' => null, 'width' => null, 'height' => null, 'thumb' => false])

@if ($path)
    @php
        $disk = Storage::disk('public');
        $thumbPath = $thumb ? \App\Services\ImageOptimizer::thumbnailPath($path) : null;
        $srcset = $thumbPath && $disk->exists($thumbPath) ? $disk->url($thumbPath).' 480w, '.$disk->url($path).' 1200w' : null;
    @endphp
    <img
        src="{{ $disk->url($path) }}"
        @if ($srcset) srcset="{{ $srcset }}" @endif
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
