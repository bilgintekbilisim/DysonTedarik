<?php

namespace App\Http\Requests;

class StoreContactMessage extends PublicFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->contactRules(),
            'phone' => ['nullable', 'regex:'.self::PHONE_REGEX],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }
}
