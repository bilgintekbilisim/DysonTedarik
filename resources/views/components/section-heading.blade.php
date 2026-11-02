@props(['title' => null, 'subtitle' => null, 'eyebrow' => null, 'link' => null, 'linkText' => null, 'center' => false, 'dark' => false])

@if ($title || $subtitle)
    <div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:mb-10 '.($center ? 'items-center text-center' : 'sm:flex-row sm:items-end sm:justify-between')]) }}>
        <div class="{{ $center ? 'max-w-2xl' : 'max-w-3xl' }}">
            @if ($eyebrow)
                <p class="eyebrow mb-2 {{ $dark ? '!text-accent-400' : '' }}">{{ $eyebrow }}</p>
            @endif
            @if ($title)
                <h2 class="text-2xl sm:text-3xl {{ $dark ? '!text-white' : '' }}">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <p class="mt-3 text-base {{ $dark ? 'text-navy-200' : 'text-slate-600' }}">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($link && $linkText && ! $center)
            <a href="{{ $link }}" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-accent-600 hover:text-accent-700">
                {{ $linkText }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
            </a>
        @endif
    </div>
@endif
