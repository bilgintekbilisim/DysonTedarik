@if ($children)
    <li x-data="{ open: false }">
        <div class="flex items-center">
            @if ($item['url'])
                <a href="{{ $item['url'] }}" class="flex-1 rounded-lg px-3 py-3 {{ $level ? 'text-sm text-slate-700' : 'font-semibold text-navy-950' }}">{{ $item['label'] }}</a>
            @else
                <button type="button" @click="open = !open" class="flex-1 rounded-lg px-3 py-3 text-start {{ $level ? 'text-sm text-slate-700' : 'font-semibold text-navy-950' }}">{{ $item['label'] }}</button>
            @endif
            <button type="button" @click="open = !open" class="rounded-lg p-3 text-slate-500" :aria-expanded="open" aria-label="{{ $item['label'] }} alt menüsü">
                <x-heroicon-m-chevron-down class="size-5 transition" ::class="open && 'rotate-180'" aria-hidden="true" />
            </button>
        </div>
        <ul x-show="open" x-collapse x-cloak class="ms-3 border-s border-slate-200 ps-2">
            @foreach ($children as $child)
                @include('partials.mobile-menu-item', ['item' => $child, 'children' => $child['children'] ?? [], 'level' => $level + 1])
            @endforeach
        </ul>
    </li>
@elseif ($item['url'])
    <li>
        <a href="{{ $item['url'] }}" @if ($item['new_tab'] ?? false) target="_blank" rel="noopener" @endif
           class="block rounded-lg px-3 py-3 {{ $level ? 'text-sm text-slate-700' : 'font-semibold text-navy-950' }} hover:bg-navy-50">{{ $item['label'] }}</a>
    </li>
@endif
