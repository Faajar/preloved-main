<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordModerationAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProductModerationRequest;
use App\Models\Category;
use App\Models\Product;
use App\ProductStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::withTrashed()
            ->with(['category:id,name,slug', 'seller:id,name,email', 'primaryImage:id,product_id,disk,path,alt_text'])
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('q')->trim()->toString().'%';
                $query->where(function (Builder $subQuery) use ($keyword): void {
                    $subQuery->where('name', 'like', $keyword)
                        ->orWhereHas('seller', fn (Builder $sellerQuery) => $sellerQuery->where('name', 'like', $keyword));
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $status = $request->string('status')->toString();
                if ($status === 'trashed') {
                    $query->onlyTrashed();
                } else {
                    $query->whereNull('deleted_at')->where('status', $status);
                }
            })
            ->when($request->filled('category_id'), function (Builder $query) use ($request): void {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function show(int $id): View
    {
        $product = Product::withTrashed()
            ->with([
                'category',
                'seller',
                'images',
                'reports.reporter:id,name,email',
                'moderationAudits' => fn ($query) => $query->with('actor:id,name')->latest(),
            ])
            ->findOrFail($id);

        return view('admin.products.show', compact('product'));
    }

    public function update(UpdateProductModerationRequest $request, int $id, RecordModerationAudit $recordAudit): RedirectResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->trashed()) {
            return back()->withErrors(['product' => 'Produk yang sudah dihapus oleh seller tidak dapat diubah statusnya.']);
        }

        DB::transaction(function () use ($request, $product, $recordAudit): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $nextStatus = ProductStatus::from($request->string('status')->toString());

            $before = ['status' => $lockedProduct->status->value];
            $lockedProduct->update(['status' => $nextStatus]);
            $after = ['status' => $nextStatus->value];

            $action = $nextStatus === ProductStatus::Hidden ? 'product.hidden' : 'product.unhidden';

            $recordAudit->handle(
                $request->user(),
                $action,
                $lockedProduct,
                before: $before,
                after: $after,
                reason: $request->string('reason')->toString(),
            );
        });

        $message = $request->string('status')->toString() === ProductStatus::Hidden->value
            ? 'Produk berhasil disembunyikan dari marketplace publik.'
            : 'Produk berhasil dikembalikan menjadi aktif (available).';

        return redirect()->route('admin.products.show', $product->id)->with('status', $message);
    }
}
