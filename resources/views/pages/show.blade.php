@extends('layouts.app')

@section('content')
    <x-page-hero :title="$page->title" :description="$page->excerpt" :breadcrumbs="$seo->breadcrumbs" :image="$page->cover_image" />

    @if ($page->content)
        <section class="section">
            <div class="container-site">
                {{-- İçerik kayıt sırasında HtmlSanitizer ile temizlenmiştir. --}}
                <article class="prose-site mx-auto max-w-3xl">{!! $page->content !!}</article>
            </div>
        </section>
    @endif

    @include('blocks.render', ['blocks' => $blocks])

    @if (! $page->content && $blocks->isEmpty())
        <section class="section">
            <p class="container-site text-center text-slate-500">Bu sayfanın içeriği hazırlanıyor.</p>
        </section>
    @endif

    <div class="container-site pb-10 text-xs text-slate-400">
        Son güncelleme: <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->translatedFormat('d F Y') }}</time>
    </div>
@endsection
