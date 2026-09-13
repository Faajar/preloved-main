<?php

namespace App\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentReconciler
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentStatusReconciler $statusReconciler,
    ) {}

    public function reconcile(PaymentAttempt $attempt): Payment
    {
        try {
            $result = $this->gateway->checkStatus($attempt->loadMissing('payment'));
        } catch (Throwable $exception) {
            $this->recordFailure($attempt, $exception);

            throw $exception;
        }

        return DB::transaction(function () use ($attempt, $result): Payment {
            $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($lockedAttempt->payment_id);
            $this->statusReconciler->apply(
                $payment,
                $lockedAttempt,
                $result->status,
                $result->occurredAt,
                $result->orderStatus,
            );
            $lockedAttempt->update(['last_reconciled_at' => now()]);

            return $payment->fresh(['currentAttempt', 'fulfillment']);
        }, attempts: 3);
    }

    private function recordFailure(PaymentAttempt $attempt, Throwable $exception): void
    {
        DB::transaction(function () use ($attempt, $exception): void {
            $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->find($attempt->id);

            if ($lockedAttempt === null || $lockedAttempt->status->value === 'succeeded') {
                return;
            }

            $lockedAttempt->update([
                'last_reconciled_at' => now(),
                'last_provider_error' => mb_substr($exception::class.': '.$exception->getMessage(), 0, 1000),
            ]);
        });
    }
}
