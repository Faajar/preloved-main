<?php

namespace App\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookEvent;
use App\PaymentRefundKind;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\VerifiedPaymentEvent;
use App\PaymentStatus;
use App\PaymentWebhookProcessingStatus;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentWebhookHandler
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentFulfillmentService $fulfillment,
        private readonly PaymentStatusReconciler $statusReconciler,
    ) {}

    public function handle(Request $request): void
    {
        $event = $this->gateway->verifyNotification($request);

        DB::transaction(function () use ($event): void {
            $attemptId = PaymentAttempt::query()
                ->where('provider', 'doku')
                ->where('invoice_number', $event->invoiceNumber)
                ->value('id');

            if ($attemptId === null) {
                throw new RuntimeException('Payment attempt DOKU tidak ditemukan.');
            }

            $paymentId = PaymentAttempt::query()->whereKey($attemptId)->value('payment_id');
            $payment = Payment::query()->lockForUpdate()->findOrFail($paymentId);
            $attempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attemptId);
            $existingEvent = PaymentWebhookEvent::query()
                ->where('provider', 'doku')
                ->where('provider_event_reference', $event->eventReference)
                ->first();

            if ($existingEvent !== null) {
                if (! hash_equals($existingEvent->payload_hash, $event->payloadHash)) {
                    throw new RuntimeException('Request-Id DOKU digunakan ulang dengan payload berbeda.');
                }

                return;
            }

            $this->assertEventMatches($payment, $attempt, $event);

            try {
                $receipt = PaymentWebhookEvent::query()->create([
                    'provider' => 'doku',
                    'provider_event_reference' => $event->eventReference,
                    'payment_id' => $payment->id,
                    'payment_attempt_id' => $attempt->id,
                    'payload_hash' => $event->payloadHash,
                    'status' => $event->status,
                    'provider_status' => $event->status,
                    'processing_status' => PaymentWebhookProcessingStatus::Received,
                    'processing_attempts' => 1,
                    'received_at' => now(),
                ]);
            } catch (QueryException $exception) {
                $existingEvent = PaymentWebhookEvent::query()
                    ->where('provider', 'doku')
                    ->where('provider_event_reference', $event->eventReference)
                    ->first();

                if ($existingEvent === null) {
                    throw $exception;
                }

                if (! hash_equals($existingEvent->payload_hash, $event->payloadHash)) {
                    throw new RuntimeException('Request-Id DOKU digunakan ulang dengan payload berbeda.');
                }

                return;
            }

            $handled = $this->applyStatus($payment, $attempt, $event);
            $receipt->update([
                'processing_status' => $handled
                    ? PaymentWebhookProcessingStatus::Processed
                    : PaymentWebhookProcessingStatus::Unhandled,
                'processed_at' => $handled ? now() : null,
                'processing_error' => $handled ? null : 'Status provider belum dikenali.',
            ]);
        }, attempts: 3);
    }

    private function assertEventMatches(Payment $payment, PaymentAttempt $attempt, VerifiedPaymentEvent $event): void
    {
        if ($payment->amount !== $event->amount) {
            throw new RuntimeException('Nominal notifikasi DOKU tidak cocok.');
        }

        if ($event->status !== 'REFUNDED'
            && $event->originalRequestId !== null
            && ! hash_equals((string) $attempt->provider_request_id, $event->originalRequestId)) {
            throw new RuntimeException('Original Request-Id DOKU tidak cocok.');
        }

        if ($payment->product === null || $payment->product->seller_id !== $payment->user_id) {
            throw new RuntimeException('Kepemilikan payment tidak valid.');
        }
    }

    private function applyStatus(Payment $payment, PaymentAttempt $attempt, VerifiedPaymentEvent $event): bool
    {
        if ($event->status !== 'REFUNDED') {
            return $this->statusReconciler->apply($payment, $attempt, $event->status, $event->occurredAt);
        }

        if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)) {
            return false;
        }

        if ($event->refundStatus !== null && $event->refundStatus !== 'SUCCESS') {
            return false;
        }

        $knownRefundTypes = [
            'VOID',
            'VOIDED',
            'FULL_REFUND',
            'MANUAL_FULL_REFUND',
            'PARTIAL_REFUND',
            'MANUAL_PARTIAL_REFUND',
        ];

        if ($event->refundType !== null && ! in_array($event->refundType, $knownRefundTypes, true)) {
            return false;
        }

        $refundAmount = $event->refundAmount ?? $event->amount;
        $providerReference = $event->refundReference ?? $event->eventReference;
        $existingRefund = $payment->refunds()
            ->where('provider', 'doku')
            ->where('provider_refund_reference', $providerReference)
            ->first();

        if ($existingRefund !== null) {
            if ($existingRefund->amount !== $refundAmount) {
                throw new RuntimeException('Reference refund DOKU digunakan ulang dengan nominal berbeda.');
            }

            return true;
        }

        if ($payment->status === PaymentStatus::Refunded) {
            return true;
        }

        $refundedBefore = (int) $payment->refunds()->sum('amount');

        if ($refundAmount > $payment->amount - $refundedBefore) {
            throw new RuntimeException('Total nominal refund DOKU melebihi nominal payment.');
        }

        $refundKind = $refundAmount === $payment->amount
            ? PaymentRefundKind::Full
            : PaymentRefundKind::Partial;
        $isFullyRefunded = $refundedBefore + $refundAmount === $payment->amount;
        $providerStatus = $event->refundStatus ?? $event->status;

        $payment->refunds()->create([
            'provider' => 'doku',
            'provider_refund_reference' => $providerReference,
            'payment_attempt_id' => $attempt->id,
            'provider_status' => $providerStatus,
            'kind' => $refundKind,
            'amount' => $refundAmount,
            'currency' => $payment->currency,
            'reason' => $event->refundReason,
            'occurred_at' => $event->occurredAt ?? now(),
            'received_at' => now(),
            'requires_review' => $refundKind === PaymentRefundKind::Partial,
        ]);

        if (! $isFullyRefunded) {
            return true;
        }

        $this->fulfillment->reverseRefund($payment);
        $payment->update([
            'status' => PaymentStatus::Refunded,
            'refunded_at' => $event->occurredAt ?? now(),
        ]);

        return true;
    }
}
