<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordModerationAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteCategoryRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request, RecordModerationAudit $recordAudit): RedirectResponse
    {
        $category = DB::transaction(function () use ($request, $recordAudit): Category {
            $category = Category::query()->create([
                ...$request->safe()->only(['name', 'slug', 'description']),
                'is_active' => true,
            ]);

            $recordAudit->handle(
                $request->user(),
                'category.created',
                $category,
                after: $category->only(['name', 'slug', 'description', 'is_active']),
                reason: $request->string('reason')->toString(),
            );

            return $category;
        });

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Kategori berhasil dibuat dan langsung aktif.');
    }

    public function edit(Category $category): View
    {
        $category->loadCount('products');
        $audits = $category->moderationAudits()->with('actor:id,name')->latest()->limit(20)->get();

        return view('admin.categories.edit', compact('category', 'audits'));
    }

    public function update(UpdateCategoryRequest $request, Category $category, RecordModerationAudit $recordAudit): RedirectResponse
    {
        DB::transaction(function () use ($request, $category, $recordAudit): void {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->id);
            $before = $lockedCategory->only(['name', 'slug', 'description', 'is_active']);
            $lockedCategory->update($request->safe()->only(['name', 'description']));
            $after = $lockedCategory->only(['name', 'slug', 'description', 'is_active']);

            if ($before !== $after) {
                $recordAudit->handle(
                    $request->user(),
                    'category.updated',
                    $lockedCategory,
                    before: $before,
                    after: $after,
                    reason: $request->string('reason')->toString(),
                );
            }
        });

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(DeleteCategoryRequest $request, Category $category, RecordModerationAudit $recordAudit): RedirectResponse
    {
        DB::transaction(function () use ($request, $category, $recordAudit): void {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->id);

            abort_if($lockedCategory->products()->exists(), 422);

            $snapshot = $lockedCategory->only(['name', 'slug', 'description', 'is_active']);
            $lockedCategory->delete();

            $recordAudit->handle(
                $request->user(),
                'category.deleted',
                null,
                before: $snapshot + ['category_id' => $lockedCategory->id],
                reason: $request->string('reason')->toString(),
            );
        });

        return redirect()->route('admin.categories.index')->with('status', 'Kategori kosong berhasil dihapus.');
    }
}
