@extends('layouts.app')

@section('content')
    <x-page-hero title="E-Katalog" description="Ürün kataloglarımızı ve broşürlerimizi PDF olarak görüntüleyin veya indirin." :breadcrumbs="$seo->breadcrumbs" />

    <section class="container-site py-10 lg:py-14">
        @if ($catalogs->isEmpty())
            <p class="text-center text-slate-500">Henüz katalog eklenmedi.</p>
        @else
            <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($catalogs as $catalog)
                    <li class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-navy-200 hover:shadow-lg hover:shadow-navy-900/5">
                        <a href="{{ $catalog->fileUrl() }}" target="_blank" rel="noopener" class="block aspect-[210/297] bg-slate-100" aria-label="{{ $catalog->title }} — PDF'i görüntüle">
                            @if ($catalog->cover_image)
                                <x-img :path="$catalog->cover_image" :alt="$catalog->title" width="420" height="594" class="size-full object-cover" />
                            @else
                                <span class="flex size-full flex-col items-center justify-center gap-3 bg-navy-950 p-6 text-center text-white">
                                    <x-heroicon-o-book-open class="size-12 text-accent-500" aria-hidden="true" />
                                    <span class="text-lg font-semibold">{{ $catalog->title }}</span>
                                </span>
                            @endif
                        </a>
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <div>
                                @if ($catalog->brand)
                                    <p class="text-xs font-semibold uppercase tracking-wide text-accent-600">{{ $catalog->brand->name }}</p>
                                @endif
                                <h2 class="mt-1 font-semibold text-navy-950">{{ $catalog->title }}</h2>
                                @if ($catalog->description)
                                    <p class="mt-1.5 text-sm text-slate-600">{{ $catalog->description }}</p>
                                @endif
                            </div>
                            <p class="mt-auto flex items-center gap-1.5 text-xs text-slate-500">
                                <x-heroicon-o-document class="size-4" aria-hidden="true" /> PDF{{ $catalog->readableSize() ? ' · '.$catalog->readableSize() : '' }}
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ $catalog->fileUrl() }}" target="_blank" rel="noopener" class="btn-outline !px-3">
                                    <x-heroicon-o-eye class="size-4" aria-hidden="true" /> Görüntüle
                                </a>
                                <a href="{{ $catalog->fileUrl() }}" download="{{ $catalog->downloadName() }}" class="btn-primary !px-3">
                                    <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" /> İndir
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
