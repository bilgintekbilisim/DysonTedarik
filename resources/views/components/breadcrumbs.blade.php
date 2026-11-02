@props(['items' => [], 'dark' => false])

@if (count($items) > 1)
    <nav aria-label="Sayfa konumu" {{ $attributes->merge(['class' => 'text-sm']) }}>
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 {{ $dark ? 'text-navy-200' : 'text-slate-500' }}">
            @foreach ($items as $item)
                <li class="flex items-center gap-1.5">
                    @if (! $loop->first)
                        <x-heroicon-m-chevron-right class="size-4 opacity-60" aria-hidden="true" />
                    @endif
                    @if ($item['url'] && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="hover:underline {{ $dark ? 'hover:text-white' : 'hover:text-navy-900' }}">{{ $item['label'] }}</a>
                    @else
                        <span @if ($loop->last) aria-current="page" @endif class="{{ $dark ? 'text-white' : 'text-navy-900' }} font-medium">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
