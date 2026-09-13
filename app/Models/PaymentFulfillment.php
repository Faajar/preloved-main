<?php

namespace App\Models;

use App\PaymentFulfillmentStatus;
use Database\Factories\PaymentFulfillmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id',
    'status',
    'boost_duration_hours',
    'fulfilled_at',
    'reversed_at',
    'last_failure',
    'attempt_count',
    'next_retry_at',
])]
class PaymentFulfillment extends Model
{
    /** @use HasFactory<PaymentFulfillmentFactory> */
    use HasFactory;

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PaymentFulfillmentStatus::class,
            'boost_duration_hours' => 'integer',
            'fulfilled_at' => 'datetime',
            'reversed_at' => 'datetime',
            'attempt_count' => 'integer',
            'next_retry_at' => 'datetime',
        ];
    }
}
