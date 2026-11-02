@php $allowedIcons = array_keys(\App\Support\Blocks\BlockRegistry::ICONS); @endphp

<section class="section bg-slate-50">
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" :subtitle="$data['subtitle'] ?? null" :center="true" />

        <ul class="{{ $variant === 'list' ? 'mx-auto grid max-w-4xl gap-6 sm:grid-cols-2' : 'grid gap-5 sm:grid-cols-2 lg:grid-cols-3' }}">
            @foreach ($data['items'] as $item)
                @php $icon = in_array($item['icon'] ?? '', $allowedIcons, true) ? $item['icon'] : 'check-badge'; @endphp
                <li class="{{ $variant === 'list' ? 'flex gap-4' : 'rounded-2xl border border-slate-200 bg-white p-6 transition hover:shadow-lg hover:shadow-navy-900/5' }}">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-accent-400">
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" aria-hidden="true" />
                    </span>
                    <div class="{{ $variant === 'list' ? '' : 'mt-5' }}">
                        <h3 class="text-lg">{{ $item['title'] }}</h3>
                        @if ($item['text'] ?? null)
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['text'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
