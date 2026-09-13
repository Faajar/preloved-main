<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $latestProducts = Product::query()
            ->publiclyVisible()
            ->withActiveBoost()
            ->with(['category:id,name,slug', 'seller:id,name', 'primaryImage:id,product_id,disk,path,alt_text'])
            ->boostedFirst()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('home', compact('categories', 'latestProducts'));
    }
}
