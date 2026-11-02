{{-- Etiketli form alanı; sunucu doğrulama hatasını alanın altında gösterir ve aria ile bağlar. --}}
@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => null, 'rows' => null, 'hint' => null])

@php
    $id = 'f-'.$name;
    $hasError = $errors->has($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($hasError ? $id.'-error' : ''));
    $classes = 'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-navy-950 placeholder:text-slate-400 focus:ring-2 focus:outline-none '
        .($hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-navy-400 focus:ring-navy-100');
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-navy-950">
        {{ $label }} @if ($required)<span class="text-accent-600" aria-hidden="true">*</span>@endif
    </label>
    @if ($rows)
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
                  @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($hasError) aria-invalid="true" @endif
                  {{ $attributes->except('class') }} class="{{ $classes }}">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($hasError) aria-invalid="true" @endif
               {{ $attributes->except('class') }} class="{{ $classes }}">
    @endif
    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
