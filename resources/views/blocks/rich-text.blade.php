<section class="section">
    <div class="container-site">
        <div class="{{ $variant === 'wide' ? 'max-w-none' : 'mx-auto max-w-3xl' }}">
            @if ($data['title'] ?? null)
                <h2 class="mb-6 text-2xl sm:text-3xl">{{ $data['title'] }}</h2>
            @endif
            {{-- İçerik kayıt sırasında HtmlSanitizer ile temizlenmiştir. --}}
            <div class="prose-site">{!! $data['content'] ?? '' !!}</div>
        </div>
    </div>
</section>
