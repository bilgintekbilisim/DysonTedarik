<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b142a">

    @include('partials.seo-head')

    @if ($favicon = $site->get('general.favicon'))
        <link rel="icon" type="image/png" href="{{ Storage::disk('public')->url($favicon) }}">
        <link rel="apple-touch-icon" href="{{ Storage::disk('public')->url($favicon) }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    <a href="#icerik" class="sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold text-navy-950 focus:not-sr-only focus:fixed focus:start-4 focus:top-4">İçeriğe geç</a>

    @if ($preview ?? false)
        <div class="bg-accent-500 px-4 py-2 text-center text-sm font-semibold text-navy-950" role="status">
            Önizleme modu — bu görünüm yalnızca panel kullanıcılarına açıktır.
        </div>
    @endif

    @include('partials.header')

    <main id="icerik" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    @if ($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
           class="wa-float fixed end-4 bottom-4 z-40 flex size-14 items-center justify-center rounded-full bg-[#25d366] text-white shadow-lg shadow-black/20 transition hover:scale-105 sm:end-6 sm:bottom-6"
           aria-label="WhatsApp ile iletişime geçin">
            {!! \App\Support\BrandIcons::svg('whatsapp', 'size-7') !!}
        </a>
    @endif

    @include('partials.cookie-consent')
</body>
</html>
