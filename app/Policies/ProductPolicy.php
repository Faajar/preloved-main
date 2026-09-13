<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\UserRole;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->canSell();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function markSold(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function boost(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function report(User $user, Product $product): bool
    {
        return $user->role !== UserRole::Admin
            && $user->id !== $product->seller_id
            && Product::query()->publiclyVisible()->whereKey($product->id)->exists();
    }
}
