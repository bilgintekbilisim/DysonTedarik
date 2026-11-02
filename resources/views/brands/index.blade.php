@extends('layouts.app')

@section('content')
    <x-page-hero title="Markalar" description="Tedarik ettiğimiz markalar ve ürün grupları." :breadcrumbs="$seo->breadcrumbs" />

    <section class="container-site py-10 lg:py-14">
        @if ($brands->isEmpty())
            <p class="text-center text-slate-500">Henüz marka eklenmedi.</p>
        @else
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($brands as $brand)
                    <li>
                        <a href="{{ route('brands.show', $brand) }}" class="group flex h-full flex-col items-center gap-4 rounded-xl border border-slate-200 bg-white p-6 text-center transition hover:border-navy-200 hover:shadow-lg hover:shadow-navy-900/5">
                            <span class="flex h-16 w-full items-center justify-center">
                                @if ($brand->logo)
                                    <x-img :path="$brand->logo" :alt="$brand->name" width="160" height="64" class="max-h-16 w-auto object-contain" />
                                @else
                                    <span class="text-lg font-bold text-navy-900">{{ $brand->name }}</span>
                                @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-navy-950">{{ $brand->name }}</span>
                                <span class="text-xs text-slate-500">{{ $brand->products_count }} ürün</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
