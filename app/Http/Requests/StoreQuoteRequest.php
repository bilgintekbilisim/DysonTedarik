<?php

namespace App\Http\Requests;

class StoreQuoteRequest extends PublicFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'product_name' => ['required', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'phone' => ['required', 'regex:'.self::PHONE_REGEX],
            'message' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
