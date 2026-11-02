@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalar" class="mt-10 flex items-center justify-between gap-4 sm:justify-center">
        @if ($paginator->onFirstPage())
            <span class="btn-outline pointer-events-none opacity-40" aria-disabled="true">
                <x-heroicon-m-chevron-left class="size-4" aria-hidden="true" /> Önceki
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-outline">
                <x-heroicon-m-chevron-left class="size-4" aria-hidden="true" /> Önceki
            </a>
        @endif

        <ul class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-2 text-slate-400">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="flex size-10 items-center justify-center rounded-lg bg-navy-900 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="flex size-10 items-center justify-center rounded-lg text-sm font-medium text-navy-900 hover:bg-navy-50" aria-label="Sayfa {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        <span class="text-sm text-slate-500 sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-outline">
                Sonraki <x-heroicon-m-chevron-right class="size-4" aria-hidden="true" />
            </a>
        @else
            <span class="btn-outline pointer-events-none opacity-40" aria-disabled="true">
                Sonraki <x-heroicon-m-chevron-right class="size-4" aria-hidden="true" />
            </span>
        @endif
    </nav>
@endif
