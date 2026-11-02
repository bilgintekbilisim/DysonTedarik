@extends('layouts.app', ['seo' => tap(new \App\Support\Seo(title: 'Sayfa bulunamadı'), fn ($s) => $s->noindex = true)])

@section('content')
    <section class="container-site py-20 text-center sm:py-28">
        <p class="text-6xl font-bold tracking-tight text-accent-500">404</p>
        <h1 class="mt-4 text-2xl sm:text-3xl">Aradığınız sayfa bulunamadı</h1>
        <p class="mx-auto mt-3 max-w-lg text-slate-600">Sayfa kaldırılmış, adı değişmiş veya geçici olarak kullanılamıyor olabilir. Ürünü arayarak bulmayı deneyin.</p>
        <div class="mx-auto mt-8 flex max-w-xl justify-center">
            @include('partials.search-box', ['id' => 'arama-404'])
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-dark">Ana sayfaya dön</a>
            <a href="{{ route('products.index') }}" class="btn-outline">Tüm ürünler</a>
        </div>
    </section>
@endsection
