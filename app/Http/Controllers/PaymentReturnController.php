<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentReturnController extends Controller
{
    public function __invoke(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        return view('payments.return', ['payment' => $payment->fresh()]);
    }
}
