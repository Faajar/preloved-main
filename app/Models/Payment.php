<?php

namespace App\Models;

use App\PaymentStatus;
use App\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'user_id',
    'product_id',
    'type',
    'amount',
    'currency',
    'status',
    'paid_at',
    'failed_at',
    'expired_at',
    'refunded_at',
    'metadata',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function boost(): HasOne
    {
        return $this->hasOne(Boost::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function currentAttempt(): HasOne
    {
        return $this->hasOne(PaymentAttempt::class)->ofMany('sequence', 'max');
    }

    public function fulfillment(): HasOne
    {
        return $this->hasOne(PaymentFulfillment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }

    public function moderationAudits(): MorphMany
    {
        return $this->morphMany(ModerationAudit::class, 'subject');
    }

    public function hasUsableCheckout(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->currentAttempt?->hasUsableCheckout() === true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'amount' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'expired_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
