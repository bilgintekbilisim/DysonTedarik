<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id', 'product_name', 'product_code', 'quantity', 'company_name', 'full_name', 'phone', 'email',
    'message', 'status', 'admin_note', 'consent_at', 'ip_address', 'user_agent',
])]
class QuoteRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'consent_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
