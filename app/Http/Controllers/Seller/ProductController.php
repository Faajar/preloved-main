<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\PaymentStatus;
use App\PaymentType;
use App\ProductStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = auth()->user()->products()
            ->withActiveBoost()
            ->with(['category', 'images', 'payments' => fn ($query) => $query->latest()])
            ->latest()
            ->paginate(12);

        return view('seller.products.index', compact('products'));
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('seller.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $settings = Setting::query()->findOrFail(1);
        $storedPaths = [];

        try {
            $product = DB::transaction(function () use ($request, $settings, &$storedPaths): Product {
                if ($request->filled('whatsapp_number')) {
                    $request->user()->update([
                        'whatsapp_number' => $request->validated('whatsapp_number'),
                    ]);
                }

                $product = $request->user()->products()->create([
                    ...$request->safe()->only(['name', 'category_id', 'price', 'condition', 'description', 'location']),
                    'slug' => $this->uniqueSlug($request->string('name')->toString()),
                    'status' => ProductStatus::Available,
                    'published_at' => $settings->listing_fee_enabled ? null : now(),
                ]);

                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store("products/{$product->id}", 'public');
                    $storedPaths[] = $path;

                    $product->images()->create([
                        'disk' => 'public',
                        'path' => $path,
                        'alt_text' => $product->name,
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                    ]);
                }

                if ($settings->listing_fee_enabled) {
                    $payment = $product->payments()->create([
                        'user_id' => $request->user()->id,
                        'type' => PaymentType::Listing,
                        'amount' => $settings->listing_fee,
                        'currency' => 'IDR',
                        'status' => PaymentStatus::Pending,
                    ]);
                    $payment->fulfillment()->create(['status' => 'pending']);
                }

                return $product;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        $message = $settings->listing_fee_enabled
            ? 'Barang tersimpan dan menunggu pembayaran listing.'
            : 'Barang berhasil dititipkan dan langsung aktif secara gratis.';

        return redirect()->route('seller.products.edit', $product)->with('status', $message);
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        $product = Product::query()->withActiveBoost()->whereKey($product->id)->firstOrFail();
        $product->load(['category', 'images', 'payments' => fn ($query) => $query->latest()]);
        $categories = Category::query()
            ->where('is_active', true)
            ->orWhereKey($product->category_id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
        $settings = Setting::query()->findOrFail(1);
        $hasOtherActiveBoost = $product->seller->boosts()
            ->active()
            ->where('product_id', '!=', $product->id)
            ->whereHas('product', fn ($query) => $query->publiclyVisible())
            ->exists();
        $canBoostProduct = Product::query()->publiclyVisible()->whereKey($product->id)->exists();

        return view('seller.products.edit', compact('product', 'categories', 'settings', 'hasOtherActiveBoost', 'canBoostProduct'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('seller.products.edit', $product)->with('status', 'Perubahan barang berhasil disimpan.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return redirect()->route('seller.products.index')->with('status', 'Barang dihapus dari daftar. Foto tetap disimpan untuk kebutuhan audit.');
    }

    private function uniqueSlug(string $name): string
    {
        do {
            $slug = Str::slug($name).'-'.Str::lower(Str::random(8));
        } while (Product::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
