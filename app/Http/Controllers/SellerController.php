<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\View\View;

class SellerController extends Controller
{
    public function show(User $seller): View
    {
        abort_unless($seller->role === UserRole::Seller, 404);

        $products = $seller->products()
            ->publiclyVisible()
            ->withActiveBoost()
            ->with(['category:id,name,slug', 'seller:id,name', 'primaryImage:id,product_id,disk,path,alt_text'])
            ->boostedFirst()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12);

        $activeListingCount = $seller->products()->publiclyVisible()->count();

        return view('sellers.show', compact('seller', 'products', 'activeListingCount'));
    }
}
