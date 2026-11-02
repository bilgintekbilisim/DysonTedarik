{{-- CSRF, bot tuzağı (gizli alan + zaman damgası) ve KVKK onayı — tüm ziyaretçi formlarında ortak. --}}
@php
    $kvkk = \App\Models\Page::query()->published()->where('slug', 'kvkk-aydinlatma-metni')->first(['id', 'slug', 'status', 'published_at']);
@endphp

@csrf
<input type="hidden" name="{{ \App\Support\SpamGuard::TIMESTAMP }}" value="{{ \App\Support\SpamGuard::token() }}">
<div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
    <label for="f-{{ \App\Support\SpamGuard::HONEYPOT }}">Bu alanı boş bırakın</label>
    <input id="f-{{ \App\Support\SpamGuard::HONEYPOT }}" type="text" name="{{ \App\Support\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off">
</div>

<div>
    <label class="flex items-start gap-3 text-sm text-slate-700">
        <input type="checkbox" name="kvkk" value="1" required @checked(old('kvkk'))
               class="mt-0.5 size-4 shrink-0 rounded border-slate-300 accent-navy-900" @error('kvkk') aria-invalid="true" @enderror>
        <span>
            Kişisel verilerimin talebimin yanıtlanması amacıyla işlenmesine ilişkin
            @if ($kvkk)
                <a href="{{ route('pages.show', $kvkk) }}" target="_blank" class="font-medium text-accent-600 underline">KVKK Aydınlatma Metni</a>'ni
            @else
                KVKK Aydınlatma Metni'ni
            @endif
            okudum. <span class="text-accent-600" aria-hidden="true">*</span>
        </span>
    </label>
    @error('kvkk')
        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
