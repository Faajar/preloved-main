<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'whatsapp_number', 'warning_count', 'suspended_until', 'suspension_reason'])]
#[Hidden(['password', 'remember_token', 'whatsapp_number'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isBuyer(): bool
    {
        return $this->role === UserRole::Buyer;
    }

    public function canSell(): bool
    {
        return $this->role->canSell();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_until !== null && $this->suspended_until->isFuture();
    }

    public function isPermanentlySuspended(): bool
    {
        return $this->isSuspended() && $this->suspended_until->year >= 2099;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
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
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function moderationAudits(): HasMany
    {
        return $this->hasMany(ModerationAudit::class, 'actor_id');
    }

    public function moderationSubjectAudits(): MorphMany
    {
        return $this->morphMany(ModerationAudit::class, 'subject');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'warning_count' => 'integer',
            'suspended_until' => 'datetime',
        ];
    }
}
