@extends('layouts.app')

@section('content')
    <h1 class="sr-only">{{ $seo->title }}</h1>

    @include('blocks.render', ['blocks' => $blocks])

    @if ($blocks->isEmpty())
        <section class="section">
            <div class="container-site text-center">
                <p class="text-slate-500">Ana sayfa içeriği henüz eklenmedi.</p>
            </div>
        </section>
    @endif
@endsection
