{{-- Ziyaretçi verileri InquiryReceived::plain() ile kaçışlanmıştır; bu yüzden {!! !!} kullanılır. --}}
<x-mail::message>
# {{ $isQuote ? 'Yeni teklif talebi' : 'Yeni iletişim mesajı' }}

<x-mail::table>
| | |
|:--|:--|
@foreach ($rows as $label => $value)
| **{{ $label }}** | {!! $value !!} |
@endforeach
</x-mail::table>

@if ($body)
**{{ $isQuote ? 'Açıklama' : 'Mesaj' }}**

<div>{!! $body !!}</div>
@endif

<x-mail::button :url="$panelUrl">
Panelde görüntüle
</x-mail::button>

Bu e-postayı yanıtladığınızda yanıt doğrudan talep sahibine gider.
</x-mail::message>
