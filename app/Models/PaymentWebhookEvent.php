<?php

namespace App\Models;

use App\PaymentWebhookProcessingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider',
    'provider_event_reference',
    'payment_id',
    'payment_attempt_id',
    'payload_hash',
    'status',
    'provider_status',
    'processing_status',
    'payload',
    'processing_attempts',
    'next_retry_at',
    'received_at',
    'processed_at',
    'processing_error',
])]
class PaymentWebhookEvent extends Model
{
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'processing_status' => PaymentWebhookProcessingStatus::class,
            'payload' => 'array',
            'processing_attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
