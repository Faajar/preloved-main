<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PrimaryProductImageController extends Controller
{
    public function __invoke(Product $product, ProductImage $productImage): RedirectResponse
    {
        Gate::authorize('update', $product);
        abort_unless($productImage->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $productImage): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);

            $orderedImages = $lockedProduct->images()->get()
                ->reject(fn (ProductImage $image): bool => $image->is($productImage))
                ->prepend($productImage);

            foreach ($orderedImages as $index => $image) {
                $image->update([
                    'sort_order' => $index,
                    'is_primary' => $image->is($productImage),
                ]);
            }
        });

        return back()->with('status', 'Foto utama berhasil diperbarui.');
    }
}
