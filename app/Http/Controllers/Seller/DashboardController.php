<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\ProductStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $products = $request->user()->products();

        $summary = [
            'active' => (clone $products)->where('status', ProductStatus::Available)->whereNotNull('published_at')->count(),
            'pending' => (clone $products)->where('status', ProductStatus::Available)->whereNull('published_at')->count(),
            'sold' => (clone $products)->where('status', ProductStatus::Sold)->count(),
            'hidden' => (clone $products)->where('status', ProductStatus::Hidden)->count(),
            'boosted' => $request->user()->boosts()->active()->whereHas('product', fn ($query) => $query->publiclyVisible())->count(),
        ];

        $latestProducts = $request->user()->products()
            ->withActiveBoost()
            ->with(['category', 'images', 'payments' => fn ($query) => $query->latest()])
            ->latest()
            ->limit(5)
            ->get();

        return view('seller.dashboard', compact('summary', 'latestProducts'));
    }
}
