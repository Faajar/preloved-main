<?php

namespace App\Models;

use Database\Factories\BoostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'user_id', 'payment_id', 'starts_at', 'ends_at'])]
class Boost extends Model
{
    /** @use HasFactory<BoostFactory> */
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->starts_at !== null
            && $this->ends_at !== null
            && $this->starts_at->lessThanOrEqualTo(now())
            && $this->ends_at->isAfter(now());
    }

    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->lessThanOrEqualTo(now());
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    #[Scope]
    protected function expired(Builder $query): Builder
    {
        return $query->whereNotNull('ends_at')->where('ends_at', '<=', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
