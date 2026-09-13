<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\PaymentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with([
                'user:id,name,email',
                'product:id,name',
                'currentAttempt',
            ])
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('q')->trim()->toString().'%';
                $query->where(function (Builder $subQuery) use ($keyword): void {
                    $subQuery->whereHas('attempts', fn (Builder $attemptQuery) => $attemptQuery->where('invoice_number', 'like', $keyword))
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword))
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', $keyword));
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')->toString()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $metrics = [
            'total_revenue' => (int) Payment::query()->where('status', PaymentStatus::Paid)->sum('amount'),
            'paid_count' => Payment::query()->where('status', PaymentStatus::Paid)->count(),
            'pending_count' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
            'refunded_count' => Payment::query()->where('status', PaymentStatus::Refunded)->count(),
        ];

        return view('admin.payments.index', compact('payments', 'metrics'));
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'user',
            'product' => fn ($query) => $query->withTrashed(),
            'attempts' => fn ($query) => $query->orderByDesc('sequence'),
            'fulfillment',
            'refunds' => fn ($query) => $query->latest(),
            'webhookEvents' => fn ($query) => $query->latest(),
            'moderationAudits.actor',
        ]);

        return view('admin.payments.show', compact('payment'));
    }
}
