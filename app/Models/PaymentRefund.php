<?php

namespace App\Models;

use App\PaymentRefundKind;
use Database\Factories\PaymentRefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'payment_attempt_id',
    'provider',
    'provider_refund_reference',
    'provider_status',
    'kind',
    'amount',
    'currency',
    'reason',
    'occurred_at',
    'received_at',
    'requires_review',
])]
class PaymentRefund extends Model
{
    /** @use HasFactory<PaymentRefundFactory> */
    use HasFactory;

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
            'kind' => PaymentRefundKind::class,
            'amount' => 'integer',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'requires_review' => 'boolean',
        ];
    }
}
