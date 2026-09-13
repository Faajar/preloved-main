<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReportRequest;
use App\Models\Product;
use App\ReportStatus;
use Illuminate\Http\RedirectResponse;

class ProductReportController extends Controller
{
    public function store(StoreProductReportRequest $request, Product $product): RedirectResponse
    {
        $report = $request->user()->reports()->firstOrCreate(
            ['product_id' => $product->id],
            [
                ...$request->safe()->only(['reason', 'description']),
                'status' => ReportStatus::Pending,
            ],
        );

        if (! $report->wasRecentlyCreated) {
            return back()->withErrors(['report' => 'Kamu sudah pernah melaporkan barang ini.']);
        }

        return back()->with('status', 'Laporan berhasil dikirim dan akan ditinjau oleh admin.');
    }
}
