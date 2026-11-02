@php
    $slides = $data['slides'];
    $contained = $variant === 'contained';
@endphp

<section class="{{ $contained ? 'container-site pt-6' : '' }}" aria-roledescription="carousel" aria-label="Öne çıkanlar">
    <div x-data="heroSlider({ count: {{ $slides->count() }}, autoplay: @js((bool) ($data['autoplay'] ?? true)) })"
         @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
         class="relative isolate overflow-hidden bg-navy-950 {{ $contained ? 'rounded-2xl' : '' }}">
        <div class="relative h-[520px] sm:h-[460px] lg:h-[560px]">
            @foreach ($slides as $i => $slide)
                <div x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif
                     x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     class="absolute inset-0" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $slides->count() }}">
                    <picture>
                        @if ($slide->image_mobile)
                            <source media="(max-width: 767px)" srcset="{{ Storage::disk('public')->url($slide->image_mobile) }}">
                        @endif
                        <img src="{{ Storage::disk('public')->url($slide->image_desktop) }}" alt="{{ $slide->image_alt ?: '' }}"
                             class="size-full object-cover sm:object-right" width="1920" height="700"
                             @if ($i === 0 && $first) fetchpriority="high" loading="eager" @else loading="lazy" @endif>
                    </picture>
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-950/90 via-navy-950/50 to-navy-950/10 sm:bg-gradient-to-r sm:from-navy-950/85 sm:via-navy-950/45 sm:to-transparent"></div>
                    <div class="container-site absolute inset-0 flex items-end pb-16 sm:items-center sm:pb-0">
                        <div class="max-w-xl">
                            @if ($i === 0)
                                <h2 class="text-3xl leading-tight !text-white sm:text-4xl lg:text-5xl">{{ $slide->title }}</h2>
                            @else
                                <p class="text-3xl leading-tight font-semibold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $slide->title }}</p>
                            @endif
                            @if ($slide->description)
                                <p class="mt-4 text-base text-navy-100 sm:text-lg">{{ $slide->description }}</p>
                            @endif
                            @if ($slide->button_text && $slide->button_url)
                                <a href="{{ $slide->button_url }}" class="btn-primary mt-7">
                                    {{ $slide->button_text }} <x-heroicon-m-arrow-right class="size-4" aria-hidden="true" />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($slides->count() > 1)
            <div class="container-site absolute inset-x-0 bottom-5 flex items-center justify-between">
                <div class="flex gap-2">
                    @foreach ($slides as $i => $slide)
                        <button type="button" @click="go({{ $i }})" class="h-1.5 rounded-full transition-all"
                                :class="current === {{ $i }} ? 'w-8 bg-accent-500' : 'w-4 bg-white/50 hover:bg-white/80'"
                                aria-label="{{ $i + 1 }}. slayta git" :aria-current="current === {{ $i }}"></button>
                    @endforeach
                </div>
                <div class="hidden gap-2 sm:flex">
                    <button type="button" @click="prev()" class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur hover:bg-white/20" aria-label="Önceki slayt">
                        <x-heroicon-m-chevron-left class="size-5" aria-hidden="true" />
                    </button>
                    <button type="button" @click="next()" class="flex size-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur hover:bg-white/20" aria-label="Sonraki slayt">
                        <x-heroicon-m-chevron-right class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        @endif
    </div>
</section>
