@extends('layouts.app')

@section('content')
    <x-page-hero
        :title="$filters['q'] ? 'Arama sonuçları' : 'Ürünler'"
        :description="$filters['q'] ? null : 'Ürün adı, kodu veya markasıyla arayın; kategori ve özelliklere göre filtreleyin.'"
        :breadcrumbs="$seo->breadcrumbs"
    />

    @include('catalog.partials.listing')
@endsection
