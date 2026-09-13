<?php

namespace App\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\PaymentAttemptStatus;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\PreparedCheckoutRequest;
use App\Payments\Exceptions\AmbiguousPaymentAttempt;
use App\PaymentStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentInitiator
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentReconciler $reconciler,
    ) {}

    public function initiate(Payment $payment): Payment
    {
        [$payment, $attempt, $request] = DB::transaction(function () use ($payment): array {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $attempt = $lockedPayment->currentAttempt()->lockForUpdate()->first();

            if (! in_array($lockedPayment->status, [PaymentStatus::Pending, PaymentStatus::Expired, PaymentStatus::Failed], true)) {
                abort(422, 'Pembayaran ini tidak dapat dilanjutkan.');
            }

            if ($attempt?->hasUsableCheckout()) {
                return [$lockedPayment, $attempt, null];
            }

            if ($attempt?->expires_at?->isPast() && $attempt->status !== PaymentAttemptStatus::Expired) {
                $attempt->update(['status' => PaymentAttemptStatus::Expired, 'expired_at' => now()]);
            }

            if ($this->requiresReplacementAttempt($lockedPayment, $attempt)) {
                $lockedPayment->update([
                    'status' => PaymentStatus::Pending,
                    'failed_at' => null,
                    'expired_at' => null,
                ]);
                $attempt = $lockedPayment->attempts()->create([
                    'sequence' => ($attempt?->sequence ?? 0) + 1,
                    'provider' => 'doku',
                    'status' => PaymentAttemptStatus::Initialized,
                    'provider_request_id' => (string) Str::uuid(),
                    'invoice_number' => $this->invoiceNumber(),
                ]);
            } elseif ($attempt === null) {
                $attempt = $lockedPayment->attempts()->create([
                    'sequence' => 1,
                    'provider' => 'doku',
                    'status' => PaymentAttemptStatus::Initialized,
                    'provider_request_id' => (string) Str::uuid(),
                    'invoice_number' => $this->invoiceNumber(),
                ]);
            }

            $lockedPayment->fulfillment()->firstOrCreate([], ['status' => 'pending']);
            $attempt = $attempt->fresh(['payment.user']);
            $request = $this->preparedRequest($attempt);
            $requestedAt = now();
            $attempt->update([
                'status' => PaymentAttemptStatus::Ambiguous,
                'request_timestamp' => $requestedAt,
                'first_requested_at' => $attempt->first_requested_at ?? $requestedAt,
                'request_count' => $attempt->request_count + 1,
                'last_provider_error' => null,
            ]);

            return [$lockedPayment->fresh(['user']), $attempt->fresh(['payment.user']), $request];
        }, attempts: 3);

        if ($request === null) {
            return $payment->setRelation('currentAttempt', $attempt);
        }

        try {
            $checkout = $this->gateway->createCheckout($attempt, $request);
        } catch (AmbiguousPaymentAttempt|ConnectionException|RequestException $exception) {
            $this->markAmbiguous($attempt, $exception);

            try {
                return $this->reconciler->reconcile($attempt);
            } catch (Throwable) {
                throw new AmbiguousPaymentAttempt(
                    'Status checkout DOKU masih direkonsiliasi. Silakan coba beberapa saat lagi.',
                    previous: $exception,
                );
            }
        } catch (Throwable $exception) {
            $this->markAmbiguous($attempt, $exception);

            throw $exception;
        }

        return DB::transaction(function () use ($payment, $attempt, $checkout): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $currentAttempt = $lockedPayment->currentAttempt()->lockForUpdate()->first();
            $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($currentAttempt?->id !== $lockedAttempt->id
                || ! in_array($lockedAttempt->status, [PaymentAttemptStatus::Ambiguous, PaymentAttemptStatus::Succeeded], true)) {
                return $lockedPayment->fresh(['currentAttempt', 'fulfillment']);
            }

            $attributes = [
                'provider_reference' => $checkout->providerReference,
                'checkout_url' => $checkout->url,
                'expires_at' => $checkout->expiresAt,
                'last_provider_error' => null,
            ];

            if ($lockedAttempt->status !== PaymentAttemptStatus::Succeeded) {
                $attributes['status'] = PaymentAttemptStatus::CheckoutCreated;
            }

            $lockedAttempt->update($attributes);

            return $lockedPayment->fresh(['currentAttempt', 'fulfillment']);
        }, attempts: 3);
    }

    private function requiresReplacementAttempt(Payment $payment, ?PaymentAttempt $attempt): bool
    {
        if (in_array($payment->status, [PaymentStatus::Expired, PaymentStatus::Failed], true)) {
            return true;
        }

        return $attempt !== null
            && in_array($attempt->status, [PaymentAttemptStatus::Expired, PaymentAttemptStatus::Failed], true);
    }

    private function preparedRequest(PaymentAttempt $attempt): PreparedCheckoutRequest
    {
        if ($attempt->request_body !== null) {
            if (! hash_equals((string) $attempt->request_payload_hash, hash('sha256', $attempt->request_body))) {
                throw new AmbiguousPaymentAttempt('Canonical request checkout tidak konsisten.');
            }

            return new PreparedCheckoutRequest(
                $attempt->request_payload ?? [],
                $attempt->request_body,
                $attempt->request_payload_hash,
            );
        }

        $request = $this->gateway->prepareCheckout($attempt);
        $attempt->update([
            'request_payload' => $request->payload,
            'request_body' => $request->body,
            'request_payload_hash' => $request->hash,
        ]);

        return $request;
    }

    private function markAmbiguous(PaymentAttempt $attempt, Throwable $exception): void
    {
        DB::transaction(function () use ($attempt, $exception): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($attempt->payment_id);
            $currentAttempt = $lockedPayment->currentAttempt()->lockForUpdate()->first();
            $lockedAttempt = PaymentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($currentAttempt?->id !== $lockedAttempt->id
                || ! in_array($lockedAttempt->status, [PaymentAttemptStatus::Initialized, PaymentAttemptStatus::Ambiguous], true)) {
                return;
            }

            $lockedAttempt->update([
                'status' => PaymentAttemptStatus::Ambiguous,
                'last_provider_error' => mb_substr($exception::class.': '.$exception->getMessage(), 0, 1000),
            ]);
        });
    }

    private function invoiceNumber(): string
    {
        return 'PIN'.now()->format('ymdHis').strtoupper(Str::random(10));
    }
}
