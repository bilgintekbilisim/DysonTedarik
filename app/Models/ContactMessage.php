<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'full_name', 'email', 'phone', 'subject', 'message', 'status', 'admin_note', 'consent_at', 'ip_address', 'user_agent',
])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'consent_at' => 'datetime',
        ];
    }
}
