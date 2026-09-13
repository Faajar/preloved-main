<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = auth()->user()->payments()
            ->with(['product', 'currentAttempt', 'fulfillment'])
            ->latest()
            ->paginate(20);

        return view('seller.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);
        $payment->load(['product', 'boost', 'currentAttempt', 'fulfillment']);

        return view('seller.payments.show', compact('payment'));
    }
}
