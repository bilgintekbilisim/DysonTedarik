@php
    $dark = $variant !== 'light';
    // Tailwind sınıfları derleme anında tarandığı için tam sınıf adları yazılır.
    $columns = match (count($data['items'])) {
        3 => 'lg:grid-cols-3',
        4 => 'lg:grid-cols-4',
        5 => 'lg:grid-cols-5',
        6 => 'lg:grid-cols-6',
        default => 'lg:grid-cols-2',
    };
@endphp

<section class="section {{ $dark ? 'bg-navy-950' : 'bg-navy-50' }}">
    <div class="container-site">
        <x-section-heading :title="$data['title'] ?? null" :center="true" :dark="$dark" />
        <dl class="grid grid-cols-2 gap-6 {{ $columns }}">
            @foreach ($data['items'] as $item)
                <div class="flex flex-col-reverse text-center">
                    <dt class="mt-2 text-sm {{ $dark ? 'text-navy-200' : 'text-slate-600' }}">{{ $item['label'] }}</dt>
                    <dd class="text-4xl font-bold tracking-tight {{ $dark ? 'text-white' : 'text-navy-950' }} sm:text-5xl">
                        {{ $item['value'] }}<span class="text-accent-500">{{ $item['suffix'] ?? '' }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
