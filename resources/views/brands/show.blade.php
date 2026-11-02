@extends('layouts.app')

@section('content')
    <x-page-hero :title="$brand->name" :breadcrumbs="$seo->breadcrumbs" :image="$brand->cover_image">
        @if ($brand->logo || $brand->website)
            <div class="mt-6 flex flex-wrap items-center gap-4">
                @if ($brand->logo)
                    <span class="flex h-16 items-center rounded-xl bg-white px-5">
                        <x-img :path="$brand->logo" :alt="$brand->name.' logosu'" width="140" height="48" class="max-h-12 w-auto object-contain" />
                    </span>
                @endif
                @if ($brand->website)
                    <a href="{{ $brand->website }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1.5 text-sm font-medium text-navy-100 hover:text-white">
                        Marka web sitesi <x-heroicon-m-arrow-top-right-on-square class="size-4" aria-hidden="true" />
                    </a>
                @endif
            </div>
        @endif
    </x-page-hero>

    @if ($brand->description && $products->onFirstPage())
        <section class="container-site pt-10">
            <div class="prose-site max-w-4xl">{!! $brand->description !!}</div>
        </section>
    @endif

    <section class="container-site py-10 lg:py-14">
        <h2 class="mb-6 text-xl">{{ $brand->name }} ürünleri <span class="text-base font-normal text-slate-500">({{ $products->total() }})</span></h2>
        @if ($products->isEmpty())
            <p class="text-slate-500">Bu markaya ait yayında ürün bulunmuyor.</p>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
@endsection
