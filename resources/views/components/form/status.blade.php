@props(['form', 'success'])

@if (session('form_success') === $form)
    <div class="mb-6 flex gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900" role="status">
        <x-heroicon-o-check-circle class="size-5 shrink-0 text-green-600" aria-hidden="true" />
        <p>{{ $success }}</p>
    </div>
@elseif ($errors->any())
    <div class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
        <x-heroicon-o-exclamation-triangle class="size-5 shrink-0 text-red-600" aria-hidden="true" />
        <p>Formda düzeltilmesi gereken alanlar var. Lütfen işaretli alanları kontrol edin.</p>
    </div>
@endif
