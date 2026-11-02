@php
    $siteName = $site->get('general.site_name', config('app.name'));
    $phone = $site->get('contact.phone');
    $email = $site->get('contact.email');
    $quoteUrl = Route::has('quote.create') ? route('quote.create') : null;
    $socials = array_filter($site->group('social'));
@endphp

<header x-data="{ mobileOpen: false, searchOpen: false }" @keydown.escape.window="mobileOpen = false" class="relative z-30">
    {{-- Üst iletişim şeridi --}}
    @if ($phone || $email || $site->get('contact.header_note'))
        <div class="bg-navy-950 text-xs text-navy-100">
            <div class="container-site flex h-9 items-center justify-between gap-4">
                <p class="hidden truncate md:block">{{ $site->get('contact.header_note') }}</p>
                <div class="flex items-center gap-5">
                    @if ($phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="inline-flex items-center gap-1.5 hover:text-white">
                            <x-heroicon-s-phone class="size-3.5" aria-hidden="true" /> {{ $phone }}
                        </a>
                    @endif
                    @if ($email)
                        <a href="mailto:{{ $email }}" class="hidden items-center gap-1.5 hover:text-white sm:inline-flex">
                            <x-heroicon-s-envelope class="size-3.5" aria-hidden="true" /> {{ $email }}
                        </a>
                    @endif
                    @if ($hours = $site->get('contact.working_hours'))
                        <span class="hidden items-center gap-1.5 lg:inline-flex">
                            <x-heroicon-s-clock class="size-3.5" aria-hidden="true" /> {{ $hours }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Logo, arama, eylemler --}}
    <div class="border-b border-slate-200 bg-white">
        <div class="container-site flex h-16 items-center gap-3 sm:h-20 lg:gap-8">
            <button type="button" class="-ms-2 rounded-lg p-2 text-navy-900 lg:hidden" @click="mobileOpen = true" aria-label="Menüyü aç" :aria-expanded="mobileOpen">
                <x-heroicon-o-bars-3 class="size-6" aria-hidden="true" />
            </button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ $siteName }} — Ana sayfa">
                @if ($logo = $site->get('general.logo'))
                    <img src="{{ Storage::disk('public')->url($logo) }}" alt="{{ $siteName }}" class="h-9 w-auto sm:h-11" width="180" height="44">
                @else
                    <span class="text-lg font-bold tracking-tight text-navy-950 sm:text-xl">
                        {{ $siteName }}<span class="text-accent-500">.</span>
                    </span>
                @endif
            </a>

            <div class="hidden flex-1 lg:block">
                @include('partials.search-box', ['id' => 'arama-masaustu'])
            </div>

            <div class="ms-auto flex items-center gap-2">
                <button type="button" class="rounded-lg p-2 text-navy-900 lg:hidden" @click="searchOpen = !searchOpen" aria-label="Ürün ara" :aria-expanded="searchOpen">
                    <x-heroicon-o-magnifying-glass class="size-6" aria-hidden="true" />
                </button>
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-outline hidden !px-4 xl:inline-flex">
                        {!! \App\Support\BrandIcons::svg('whatsapp', 'size-4 text-[#25d366]') !!} WhatsApp
                    </a>
                @endif
                @if ($quoteUrl)
                    <a href="{{ $quoteUrl }}" class="btn-primary hidden sm:inline-flex">
                        <x-heroicon-o-document-text class="size-4" aria-hidden="true" /> Teklif İste
                    </a>
                @endif
            </div>
        </div>

        <div x-show="searchOpen" x-collapse x-cloak class="border-t border-slate-100 lg:hidden">
            <div class="container-site py-3">
                @include('partials.search-box', ['id' => 'arama-mobil'])
            </div>
        </div>
    </div>

    {{-- Masaüstü navigasyon --}}
    @if ($headerMenu)
        <nav aria-label="Ana menü" class="hidden border-b border-slate-200 bg-white lg:block">
            <ul class="container-site flex items-center gap-1">
                @foreach ($headerMenu as $item)
                    @php $hasMega = $item['mega'] && $menuCategories; @endphp
                    <li class="{{ $hasMega ? '' : 'relative' }}" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @focusin="open = true" @focusout="if (! $el.contains($event.relatedTarget)) open = false">
                        @if ($hasMega || $item['children'])
                            <a href="{{ $item['url'] ?? '#' }}" @if (! $item['url']) role="button" @click.prevent="open = !open" @endif
                               class="flex items-center gap-1 px-3 py-3.5 text-sm font-semibold text-navy-900 hover:text-accent-600"
                               :aria-expanded="open" aria-haspopup="true">
                                {{ $item['label'] }} <x-heroicon-m-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" aria-hidden="true" />
                            </a>
                        @else
                            <a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                               class="block px-3 py-3.5 text-sm font-semibold text-navy-900 hover:text-accent-600 {{ $item['url'] === url()->current() ? 'text-accent-600' : '' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif

                        @if ($hasMega)
                            <div x-show="open" x-transition.opacity x-cloak class="absolute inset-x-0 top-full border-t border-slate-200 bg-white shadow-xl shadow-navy-900/10">
                                <div class="container-site grid grid-cols-4 gap-x-8 gap-y-6 py-8 xl:grid-cols-5">
                                    @foreach ($menuCategories as $category)
                                        <div>
                                            <a href="{{ $category['url'] }}" class="text-sm font-semibold text-navy-950 hover:text-accent-600">{{ $category['name'] }}</a>
                                            @if ($category['children'])
                                                <ul class="mt-2 space-y-1.5">
                                                    @foreach (array_slice($category['children'], 0, 8) as $child)
                                                        <li><a href="{{ $child['url'] }}" class="text-sm text-slate-600 hover:text-accent-600">{{ $child['name'] }}</a></li>
                                                    @endforeach
                                                    @if (count($category['children']) > 8)
                                                        <li><a href="{{ $category['url'] }}" class="text-sm font-medium text-accent-600">Tümünü gör</a></li>
                                                    @endif
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="border-t border-slate-100 bg-slate-50">
                                    <div class="container-site flex items-center justify-between py-3 text-sm">
                                        <span class="text-slate-600">Aradığınız ürünü bulamadınız mı?</span>
                                        <a href="{{ route('products.index') }}" class="font-semibold text-accent-600 hover:text-accent-700">Tüm ürünleri görüntüle →</a>
                                    </div>
                                </div>
                            </div>
                        @elseif ($item['children'])
                            <ul x-show="open" x-transition.opacity x-cloak class="absolute start-0 top-full min-w-56 rounded-b-xl border border-slate-200 bg-white py-2 shadow-xl shadow-navy-900/10">
                                @foreach ($item['children'] as $child)
                                    <li>
                                        <a href="{{ $child['url'] ?? '#' }}" @if ($child['new_tab']) target="_blank" rel="noopener" @endif class="block px-4 py-2 text-sm text-slate-700 hover:bg-navy-50 hover:text-navy-950">{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    {{-- Mobil menü --}}
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menü">
        <div x-show="mobileOpen" x-transition.opacity class="absolute inset-0 bg-navy-950/60" @click="mobileOpen = false"></div>
        <div x-show="mobileOpen" x-trap.noscroll="mobileOpen"
             x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 start-0 flex w-[85%] max-w-sm flex-col bg-white shadow-2xl">
            <div class="flex h-16 items-center justify-between border-b border-slate-200 px-4">
                <span class="font-bold text-navy-950">{{ $siteName }}</span>
                <button type="button" class="rounded-lg p-2 text-navy-900" @click="mobileOpen = false" aria-label="Menüyü kapat">
                    <x-heroicon-o-x-mark class="size-6" aria-hidden="true" />
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-2 py-3" aria-label="Mobil menü">
                <ul class="space-y-0.5">
                    @foreach ($mobileMenu ?: $headerMenu as $item)
                        @php
                            $children = $item['mega'] && $menuCategories
                                ? array_map(fn ($c) => ['label' => $c['name'], 'url' => $c['url'], 'new_tab' => false, 'children' => array_map(fn ($cc) => ['label' => $cc['name'], 'url' => $cc['url'], 'new_tab' => false, 'children' => []], $c['children'])], $menuCategories)
                                : $item['children'];
                        @endphp
                        @include('partials.mobile-menu-item', ['item' => $item, 'children' => $children, 'level' => 0])
                    @endforeach
                </ul>
            </nav>
            <div class="space-y-2 border-t border-slate-200 p-4">
                @if ($quoteUrl)
                    <a href="{{ $quoteUrl }}" class="btn-primary w-full">Teklif İste</a>
                @endif
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-whatsapp w-full">
                        {!! \App\Support\BrandIcons::svg('whatsapp', 'size-4') !!} WhatsApp ile yazın
                    </a>
                @endif
                @if ($phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn-outline w-full">
                        <x-heroicon-o-phone class="size-4" aria-hidden="true" /> {{ $phone }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</header>
