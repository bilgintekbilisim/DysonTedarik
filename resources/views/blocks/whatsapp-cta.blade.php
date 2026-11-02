@php $link = \App\Support\WhatsApp::link($data['prefill'] ?? null); @endphp

{{-- WhatsApp numarası Site Ayarları'nda yoksa blok gösterilmez. --}}
@if ($link)
    <section class="py-10 sm:py-14">
        <div class="container-site">
            <div class="flex flex-col items-start gap-6 rounded-2xl bg-[#e9fbf0] p-6 sm:flex-row sm:items-center sm:justify-between sm:p-10">
                <div class="flex items-start gap-4">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#25d366] text-white">
                        {!! \App\Support\BrandIcons::svg('whatsapp', 'size-6') !!}
                    </span>
                    <div>
                        <h2 class="text-xl sm:text-2xl">{{ $data['title'] ?? 'WhatsApp ile bilgi alın' }}</h2>
                        @if ($data['text'] ?? null)
                            <p class="mt-1.5 text-slate-700">{{ $data['text'] }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ $link }}" target="_blank" rel="noopener" class="btn-whatsapp shrink-0">
                    {{ $data['button_text'] ?? 'WhatsApp ile yazın' }}
                </a>
            </div>
        </div>
    </section>
@endif
