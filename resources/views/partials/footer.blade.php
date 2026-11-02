@php
    $siteName = $site->get('general.site_name', config('app.name'));
    $footer = $site->group('footer');
    $contact = $site->group('contact');
    $socials = array_filter($site->group('social'));
    $categories = ($footer['show_categories'] ?? true) ? array_slice($menuCategories, 0, (int) ($footer['category_limit'] ?? 8)) : [];
    $copyright = str_replace('{yil}', now()->year, $footer['copyright'] ?? '© {yil} '.$siteName);
    $socialLabels = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'x' => 'X', 'tiktok' => 'TikTok'];
@endphp

<footer x-data class="bg-navy-950 text-sm text-navy-200">
    <div class="container-site grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <a href="{{ route('home') }}" class="inline-block" aria-label="{{ $siteName }} — Ana sayfa">
                @if ($logo = $site->get('general.logo_dark') ?: $site->get('general.logo'))
                    <img src="{{ Storage::disk('public')->url($logo) }}" alt="{{ $siteName }}" class="h-10 w-auto" width="170" height="40" loading="lazy">
                @else
                    <span class="text-xl font-bold text-white">{{ $siteName }}<span class="text-accent-500">.</span></span>
                @endif
            </a>
            @if ($footer['description'] ?? null)
                <p class="mt-4 max-w-sm leading-6">{{ $footer['description'] }}</p>
            @endif
            @if (($footer['show_social'] ?? true) && $socials)
                <ul class="mt-6 flex gap-2" aria-label="Sosyal medya">
                    @foreach ($socials as $network => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="flex size-10 items-center justify-center rounded-full bg-white/5 text-navy-100 transition hover:bg-accent-500 hover:text-navy-950" aria-label="{{ $socialLabels[$network] ?? $network }}">
                                {!! \App\Support\BrandIcons::svg($network, 'size-4') !!}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($categories)
            <div class="lg:col-span-3">
                <h2 class="text-sm font-semibold !text-white">Ürün Kategorileri</h2>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($categories as $category)
                        <li><a href="{{ $category['url'] }}" class="hover:text-white">{{ $category['name'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($footerMenus['corporate'] || $footerMenus['quick'])
            <div class="lg:col-span-2">
                <h2 class="text-sm font-semibold !text-white">Kurumsal</h2>
                <ul class="mt-4 space-y-2.5">
                    @foreach ([...$footerMenus['corporate'], ...$footerMenus['quick']] as $item)
                        @if ($item['url'])
                            <li><a href="{{ $item['url'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif class="hover:text-white">{{ $item['label'] }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        @if (($footer['show_contact'] ?? true) && array_filter($contact))
            <div class="lg:col-span-3">
                <h2 class="text-sm font-semibold !text-white">İletişim</h2>
                <ul class="mt-4 space-y-3">
                    @if ($address = \App\Support\Company::address())
                        <li class="flex gap-3">
                            <x-heroicon-o-map-pin class="mt-0.5 size-5 shrink-0 text-accent-400" aria-hidden="true" />
                            @if ($contact['maps_url'] ?? null)
                                <a href="{{ $contact['maps_url'] }}" target="_blank" rel="noopener" class="hover:text-white">{{ $address }}</a>
                            @else
                                <address class="not-italic">{{ $address }}</address>
                            @endif
                        </li>
                    @endif
                    @foreach (\App\Support\Company::phones() as $phone)
                        <li class="flex gap-3">
                            <x-heroicon-o-phone class="size-5 shrink-0 text-accent-400" aria-hidden="true" />
                            <span>
                                @if ($phone['label'])<span class="text-navy-300">{{ $phone['label'] }}:</span>@endif
                                <a href="{{ $phone['href'] }}" class="hover:text-white">{{ $phone['number'] }}</a>
                            </span>
                        </li>
                    @endforeach
                    @foreach (\App\Support\Company::emails() as $email)
                        <li class="flex gap-3">
                            <x-heroicon-o-envelope class="size-5 shrink-0 text-accent-400" aria-hidden="true" />
                            <span class="min-w-0">
                                @if ($email['label'])<span class="block text-navy-300">{{ $email['label'] }}</span>@endif
                                <a href="mailto:{{ $email['address'] }}" class="break-words hover:text-white">{{ $email['address'] }}</a>
                            </span>
                        </li>
                    @endforeach
                    @if ($whatsappUrl)
                        <li class="flex gap-3">
                            <span class="text-[#25d366]">{!! \App\Support\BrandIcons::svg('whatsapp', 'size-5') !!}</span>
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp: {{ $contact['whatsapp'] }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    @php $legal = ($site->get('company.show_in_footer', true)) ? \App\Support\Company::legalLines() : []; @endphp
    @if ($legal)
        <div class="border-t border-white/10">
            <dl class="container-site flex flex-wrap gap-x-6 gap-y-1.5 py-4 text-xs text-navy-300">
                @foreach ($legal as $label => $value)
                    <div class="flex gap-1.5">
                        <dt class="text-navy-300">{{ $label }}:</dt>
                        <dd class="text-navy-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    <div class="border-t border-white/10">
        <div class="container-site flex flex-col gap-3 py-5 text-xs text-navy-300 sm:flex-row sm:items-center sm:justify-between">
            <p>{{ $copyright }}</p>
            <ul class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach ($footerMenus['legal'] as $item)
                    @if ($item['url'])
                        <li><a href="{{ $item['url'] }}" class="hover:text-white">{{ $item['label'] }}</a></li>
                    @endif
                @endforeach
                <li><button type="button" class="hover:text-white" @click="$dispatch('open-cookie-settings')">Çerez tercihleri</button></li>
            </ul>
        </div>
    </div>
</footer>
