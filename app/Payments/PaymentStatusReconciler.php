<?php

namespace App\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\PaymentAttemptStatus;
use App\PaymentStatus;
use Illuminate\Support\Carbon;

class PaymentStatusReconciler
{
    private const KNOWN_STATUSES = [
        'PENDING',
        'REDIRECT',
        'TIMEOUT',
        'FAILED',
        'EXPIRED',
        'SUCCESS',
        'REFUNDED',
    ];

    public function __construct(private readonly PaymentFulfillmentService $fulfillment) {}

    public function apply(
        Payment $payment,
        PaymentAttempt $attempt,
        string $status,
        ?Carbon $occurredAt = null,
        ?string $orderStatus = null,
    ): bool {
        $status = strtoupper($status);
        $orderStatus = $orderStatus === null ? null : strtoupper($orderStatus);
        $attempt->update(['last_provider_status' => $status]);

        if (! in_array($status, self::KNOWN_STATUSES, true)) {
            return false;
        }

        if ($status === 'SUCCESS') {
            if ($payment->status === PaymentStatus::Refunded) {
                return true;
            }

            $attempt->update([
                'status' => PaymentAttemptStatus::Succeeded,
                'succeeded_at' => $occurredAt ?? now(),
                'failed_at' => null,
                'expired_at' => null,
                'last_provider_error' => null,
            ]);
            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $payment->paid_at ?? $occurredAt ?? now(),
                'failed_at' => null,
                'expired_at' => null,
            ]);
            $this->fulfillment->fulfill($payment->fresh());

            return true;
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return true;
        }

        if ($status === 'EXPIRED' || $orderStatus === 'ORDER_EXPIRED') {
            $attempt->update([
                'status' => PaymentAttemptStatus::Expired,
                'expired_at' => $occurredAt ?? now(),
            ]);
            $payment->update([
                'status' => PaymentStatus::Expired,
                'expired_at' => $occurredAt ?? now(),
            ]);

            return true;
        }

        if (in_array($status, ['TIMEOUT', 'FAILED'], true)) {
            $attempt->update(['status' => PaymentAttemptStatus::Ambiguous]);
        }

        return true;
    }
}
