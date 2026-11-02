@extends('layouts.app')

@section('content')
    <x-page-hero :title="$category->name" :description="$category->summary" :breadcrumbs="$seo->breadcrumbs" :image="$category->image" />

    @include('catalog.partials.listing')

    @if ($category->description && $products->onFirstPage())
        <section class="border-t border-slate-200 bg-slate-50">
            <div class="container-site py-12">
                {{-- Açıklama kayıt sırasında HtmlSanitizer ile temizlenmiştir. --}}
                <div class="prose-site max-w-4xl">{!! $category->description !!}</div>
            </div>
        </section>
    @endif
@endsection
