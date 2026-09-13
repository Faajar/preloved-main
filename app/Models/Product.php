<?php

namespace App\Models;

use App\ProductCondition;
use App\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['seller_id', 'category_id', 'name', 'slug', 'description', 'price', 'condition', 'location', 'status', 'published_at'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function moderationAudits(): MorphMany
    {
        return $this->morphMany(ModerationAudit::class, 'subject');
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ProductStatus::Available)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('is_active', true));
    }

    #[Scope]
    protected function withActiveBoost(Builder $query): Builder
    {
        return $query->addSelect([
            'active_boost_started_at' => Boost::query()
                ->select('starts_at')
                ->whereColumn('product_id', 'products.id')
                ->active()
                ->oldest('starts_at')
                ->limit(1),
            'active_boost_ends_at' => Boost::query()
                ->select('ends_at')
                ->whereColumn('product_id', 'products.id')
                ->active()
                ->oldest('starts_at')
                ->limit(1),
        ])->withCasts([
            'active_boost_started_at' => 'datetime',
            'active_boost_ends_at' => 'datetime',
        ]);
    }

    #[Scope]
    protected function boostedFirst(Builder $query): Builder
    {
        return $query
            ->orderByRaw('active_boost_started_at IS NULL')
            ->orderBy('active_boost_started_at');
    }

    public function isBoosted(): bool
    {
        return $this->active_boost_started_at !== null && $this->active_boost_ends_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'condition' => ProductCondition::class,
            'status' => ProductStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
