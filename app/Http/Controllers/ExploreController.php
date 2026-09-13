<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExploreRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ExploreController extends Controller
{
    public function __invoke(ExploreRequest $request): View
    {
        $filters = $request->validated();
        $products = Product::query()
            ->publiclyVisible()
            ->withActiveBoost()
            ->with(['category:id,name,slug', 'seller:id,name', 'primaryImage:id,product_id,disk,path,alt_text'])
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('q')->trim()->toString().'%';

                $query->where(function (Builder $keywordQuery) use ($keyword): void {
                    $keywordQuery->where('name', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword);
                });
            })
            ->when($request->filled('category'), fn (Builder $query): Builder => $query->whereHas(
                'category',
                fn (Builder $categoryQuery): Builder => $categoryQuery->where('slug', $request->string('category')->toString())->where('is_active', true),
            ))
            ->when($request->filled('min_price'), fn (Builder $query): Builder => $query->where('price', '>=', $request->integer('min_price')))
            ->when($request->filled('max_price'), fn (Builder $query): Builder => $query->where('price', '<=', $request->integer('max_price')))
            ->when($request->filled('condition'), fn (Builder $query): Builder => $query->where('condition', $request->string('condition')->toString()))
            ->when($request->filled('location'), fn (Builder $query): Builder => $query->where('location', 'like', '%'.$request->string('location')->trim()->toString().'%'));

        $products->boostedFirst();

        match ($request->string('sort')->toString()) {
            'price_low' => $products->orderBy('price')->orderByDesc('id'),
            'price_high' => $products->orderByDesc('price')->orderByDesc('id'),
            default => $products->orderByDesc('published_at')->orderByDesc('id'),
        };

        $products = $products->paginate(12)->withQueryString();
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('explore.index', compact('products', 'categories', 'filters'));
    }
}
