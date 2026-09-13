<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\Exceptions\AmbiguousPaymentAttempt;
use App\Payments\PaymentInitiator;
use App\PaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

class PaymentCheckoutController extends Controller
{
    public function __invoke(Payment $payment, PaymentInitiator $initiator): RedirectResponse
    {
        Gate::authorize('retry', $payment);

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Expired, PaymentStatus::Failed], true)) {
            return back()->withErrors(['payment' => 'Pembayaran ini tidak dapat dilanjutkan.']);
        }

        try {
            $payment = $initiator->initiate($payment);
        } catch (AmbiguousPaymentAttempt $exception) {
            report($exception);

            return redirect()->route('seller.payments.show', $payment)
                ->with('status', 'Status checkout sedang direkonsiliasi dengan DOKU.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Checkout DOKU belum dapat dibuat. Silakan coba lagi.']);
        }

        if (! $payment->hasUsableCheckout()) {
            return redirect()->route('seller.payments.show', $payment)
                ->with('status', 'Status pembayaran telah diperbarui dari DOKU.');
        }

        return redirect()->away($payment->currentAttempt->checkout_url);
    }
}
