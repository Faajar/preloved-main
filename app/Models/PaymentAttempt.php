<?php

namespace App\Models;

use App\PaymentAttemptStatus;
use Database\Factories\PaymentAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'sequence',
    'provider',
    'status',
    'provider_request_id',
    'invoice_number',
    'provider_reference',
    'checkout_url',
    'expires_at',
    'succeeded_at',
    'failed_at',
    'expired_at',
    'request_payload',
    'request_body',
    'request_payload_hash',
    'request_timestamp',
    'first_requested_at',
    'last_reconciled_at',
    'last_provider_status',
    'request_count',
    'last_provider_error',
])]
class PaymentAttempt extends Model
{
    /** @use HasFactory<PaymentAttemptFactory> */
    use HasFactory;

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function hasUsableCheckout(): bool
    {
        return $this->status === PaymentAttemptStatus::CheckoutCreated
            && $this->checkout_url !== null
            && $this->expires_at?->isFuture();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => PaymentAttemptStatus::class,
            'expires_at' => 'datetime',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
            'expired_at' => 'datetime',
            'request_payload' => 'array',
            'request_timestamp' => 'datetime',
            'first_requested_at' => 'datetime',
            'last_reconciled_at' => 'datetime',
            'request_count' => 'integer',
        ];
    }
}
