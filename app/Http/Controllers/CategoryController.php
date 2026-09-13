<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products as public_products_count' => fn ($query) => $query->publiclyVisible()])
            ->orderByDesc('public_products_count')
            ->orderBy('name')
            ->paginate(12);

        return view('categories.index', compact('categories'));
    }

    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        $products = $category->products()
            ->publiclyVisible()
            ->withActiveBoost()
            ->with(['category:id,name,slug', 'seller:id,name', 'primaryImage:id,product_id,disk,path,alt_text'])
            ->boostedFirst()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('categories.show', compact('category', 'products'));
    }
}
