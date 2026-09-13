<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordModerationAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCategoryActivationRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CategoryActivationController extends Controller
{
    public function __invoke(
        UpdateCategoryActivationRequest $request,
        Category $category,
        RecordModerationAudit $recordAudit,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $category, $recordAudit): void {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->id);
            $nextState = ! $lockedCategory->is_active;

            $recordAudit->handle(
                $request->user(),
                $nextState ? 'category.activated' : 'category.deactivated',
                $lockedCategory,
                before: ['is_active' => $lockedCategory->is_active],
                after: ['is_active' => $nextState],
                reason: $request->string('reason')->toString(),
            );

            $lockedCategory->update(['is_active' => $nextState]);
        });

        $message = $category->refresh()->is_active
            ? 'Kategori diaktifkan dan dapat digunakan kembali.'
            : 'Kategori dinonaktifkan. Listing terkait tidak lagi tampil publik.';

        return redirect()->route('admin.categories.edit', $category)->with('status', $message);
    }
}
