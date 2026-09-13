<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\ProductStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SoldProductController extends Controller
{
    public function __invoke(Product $product): RedirectResponse
    {
        Gate::authorize('markSold', $product);
        abort_if($product->status === ProductStatus::Hidden, 403);

        if ($product->published_at === null) {
            return back()->withErrors(['product' => 'Listing yang belum aktif tidak dapat ditandai terjual.']);
        }

        if ($product->status !== ProductStatus::Sold) {
            $product->update(['status' => ProductStatus::Sold]);
        }

        return back()->with('status', 'Barang telah ditandai terjual.');
    }
}
