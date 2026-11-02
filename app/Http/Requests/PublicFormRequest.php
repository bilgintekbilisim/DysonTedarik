<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Ziyaretçi formları için ortak kurallar ve Türkçe hata mesajları. */
abstract class PublicFormRequest extends FormRequest
{
    public const PHONE_REGEX = '/^\+?[0-9 ()\-]{10,20}$/';

    public function authorize(): bool
    {
        return true;
    }

    /** Ortak kişi bilgisi ve KVKK onayı kuralları. */
    protected function contactRules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'kvkk' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute alanı zorunludur.',
            'min' => ':attribute en az :min karakter olmalıdır.',
            'max' => ':attribute en fazla :max karakter olabilir.',
            'email' => 'Geçerli bir e-posta adresi girin.',
            'regex' => ':attribute biçimi geçersiz.',
            'accepted' => 'Devam etmek için KVKK Aydınlatma Metni\'ni okuduğunuzu onaylayın.',
            'string' => ':attribute metin olmalıdır.',
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'Ad soyad',
            'email' => 'E-posta',
            'phone' => 'Telefon',
            'company_name' => 'Firma adı',
            'product_name' => 'Ürün adı',
            'product_code' => 'Ürün kodu',
            'quantity' => 'Talep miktarı',
            'subject' => 'Konu',
            'message' => 'Mesaj',
        ];
    }
}
