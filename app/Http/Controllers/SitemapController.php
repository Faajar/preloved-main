<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->publiclyVisible()
            ->select(['id', 'slug', 'updated_at'])
            ->latest('updated_at')
            ->limit(1000)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->select(['id', 'slug', 'updated_at'])
            ->get();

        $sellers = User::query()
            ->where('role', UserRole::Seller)
            ->whereHas('products', fn ($q) => $q->publiclyVisible())
            ->select(['id', 'updated_at'])
            ->get();

        $content = view('sitemap', compact('products', 'categories', 'sellers'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
