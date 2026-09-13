<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): RedirectResponse
    {
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $product, &$storedPaths): void {
                $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
                $currentCount = $lockedProduct->images()->count();
                $uploadedFiles = $request->file('images', []);

                abort_if($currentCount + count($uploadedFiles) > 5, 422, 'Total foto barang tidak boleh lebih dari lima.');

                $nextOrder = ((int) $lockedProduct->images()->max('sort_order')) + 1;
                $hasImages = $currentCount > 0;

                foreach ($uploadedFiles as $index => $image) {
                    $path = $image->store("products/{$lockedProduct->id}", 'public');
                    $storedPaths[] = $path;

                    $lockedProduct->images()->create([
                        'disk' => 'public',
                        'path' => $path,
                        'alt_text' => $lockedProduct->name,
                        'sort_order' => $hasImages ? $nextOrder + $index : $index,
                        'is_primary' => ! $hasImages && $index === 0,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        return back()->with('status', 'Foto barang berhasil ditambahkan.');
    }

    public function destroy(Product $product, ProductImage $productImage): RedirectResponse
    {
        Gate::authorize('update', $product);
        abort_unless($productImage->product_id === $product->id, 404);

        if ($product->images()->count() <= 1) {
            return back()->withErrors(['images' => 'Foto terakhir tidak dapat dihapus. Setiap barang wajib memiliki minimal satu foto.']);
        }

        $disk = $productImage->disk;
        $path = $productImage->path;

        DB::transaction(function () use ($product, $productImage): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);

            abort_if($lockedProduct->images()->count() <= 1, 422, 'Foto terakhir tidak dapat dihapus.');

            $wasPrimary = $productImage->is_primary;
            $productImage->delete();

            $remainingImages = $lockedProduct->images()->get();

            foreach ($remainingImages as $index => $image) {
                $image->update([
                    'sort_order' => $index,
                    'is_primary' => $wasPrimary ? $index === 0 : $image->is_primary,
                ]);
            }
        });

        Storage::disk($disk)->delete($path);

        return back()->with('status', 'Foto barang berhasil dihapus.');
    }
}
