<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexReportRequest;
use App\Http\Requests\Admin\UpdateReportRequest;
use App\Models\Report;
use App\ProductStatus;
use App\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(IndexReportRequest $request): View
    {
        $reports = Report::query()
            ->with(['reporter:id,name,email', 'product:id,seller_id,category_id,name,status,deleted_at'])
            ->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function show(Report $report): View
    {
        $report->load([
            'reporter:id,name,email',
            'product' => fn ($query) => $query->withTrashed()->with(['seller:id,name', 'category:id,name']),
        ]);

        return view('admin.reports.show', compact('report'));
    }

    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        DB::transaction(function () use ($request, $report): void {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);
            $nextStatus = $request->enum('status', ReportStatus::class);

            abort_unless($nextStatus !== null && $lockedReport->status->canTransitionTo($nextStatus), 422);

            if ($request->boolean('hide_product')) {
                $lockedReport->product()->withTrashed()->firstOrFail()->update(['status' => ProductStatus::Hidden]);
            }

            $lockedReport->update(['status' => $nextStatus]);
        });

        return redirect()->route('admin.reports.show', $report)->with('status', 'Status laporan berhasil diperbarui.');
    }
}
