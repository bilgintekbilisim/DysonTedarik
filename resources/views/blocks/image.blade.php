@php $full = $variant === 'full'; @endphp

@if ($data['image'] ?? null)
    <section class="{{ $full ? '' : 'container-site' }} py-8">
        <figure>
            @if ($data['url'] ?? null)
                <a href="{{ $data['url'] }}" class="block">
            @endif
            <x-img :path="$data['image']" :alt="$data['image_alt'] ?? ''" width="1600" height="900"
                   class="h-auto w-full {{ $full ? '' : 'rounded-2xl' }} object-cover" />
            @if ($data['url'] ?? null)
                </a>
            @endif
            @if ($data['caption'] ?? null)
                <figcaption class="mt-3 text-center text-sm text-slate-500">{{ $data['caption'] }}</figcaption>
            @endif
        </figure>
    </section>
@endif
