<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')->orderBy('id')->chunkById(100, function ($payments): void {
            foreach ($payments as $payment) {
                $attemptId = $this->backfillAttempt($payment);
                $this->backfillFulfillment($payment);
                $this->backfillRefund($payment, $attemptId);
            }
        });

        DB::table('payment_webhook_events')->orderBy('id')->chunkById(100, function ($events): void {
            foreach ($events as $event) {
                $attemptId = DB::table('payment_attempts')
                    ->where('payment_id', $event->payment_id)
                    ->when($event->status !== null, function ($query) use ($event): void {
                        if ($event->status === 'SUCCESS') {
                            $query->where('status', 'succeeded');
                        }
                    })
                    ->orderByDesc('sequence')
                    ->value('id');

                DB::table('payment_webhook_events')->where('id', $event->id)->update([
                    'payment_attempt_id' => $attemptId,
                    'provider_status' => $event->status,
                    'processing_status' => $event->processed_at === null ? 'received' : 'processed',
                    'processing_attempts' => $event->processed_at === null ? 0 : 1,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('payment_webhook_events')->update([
            'payment_attempt_id' => null,
            'provider_status' => null,
            'processing_status' => 'received',
            'payload' => null,
            'processing_attempts' => 0,
            'next_retry_at' => null,
        ]);
        DB::table('payment_refunds')->delete();
        DB::table('payment_fulfillments')->delete();
        DB::table('payment_attempts')->delete();
    }

    private function backfillAttempt(object $payment): ?int
    {
        if ($payment->provider === null
            && $payment->provider_request_id === null
            && $payment->provider_reference === null
            && $payment->external_reference === null
            && $payment->checkout_url === null
            && $payment->expires_at === null) {
            return null;
        }

        $attemptId = DB::table('payment_attempts')
            ->where('payment_id', $payment->id)
            ->where('sequence', 1)
            ->value('id');

        if ($attemptId !== null) {
            return (int) $attemptId;
        }

        return (int) DB::table('payment_attempts')->insertGetId([
            'payment_id' => $payment->id,
            'sequence' => 1,
            'provider' => $payment->provider ?? 'doku',
            'status' => $this->attemptStatus($payment),
            'provider_request_id' => $payment->provider_request_id,
            'invoice_number' => $payment->external_reference,
            'provider_reference' => $payment->provider_reference,
            'checkout_url' => $payment->checkout_url,
            'expires_at' => $payment->expires_at,
            'succeeded_at' => in_array($payment->status, ['paid', 'refunded'], true) ? $payment->paid_at : null,
            'failed_at' => $payment->status === 'failed' ? $payment->failed_at : null,
            'expired_at' => $payment->status === 'expired' ? $payment->expired_at : null,
            'request_payload' => null,
            'request_payload_hash' => null,
            'request_timestamp' => null,
            'request_count' => $payment->checkout_url === null ? 0 : 1,
            'last_provider_error' => null,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ]);
    }

    private function backfillFulfillment(object $payment): void
    {
        DB::table('payment_fulfillments')->insertOrIgnore([
            'payment_id' => $payment->id,
            'status' => $payment->fulfilled_at !== null
                ? ($payment->status === 'refunded' ? 'reversed' : 'fulfilled')
                : ($payment->fulfillment_failure !== null ? 'failed' : 'pending'),
            'boost_duration_hours' => $payment->boost_duration_hours,
            'fulfilled_at' => $payment->fulfilled_at,
            'reversed_at' => $payment->status === 'refunded' && $payment->fulfilled_at !== null ? $payment->refunded_at : null,
            'last_failure' => $payment->fulfillment_failure,
            'attempt_count' => $payment->fulfilled_at !== null || $payment->fulfillment_failure !== null ? 1 : 0,
            'next_retry_at' => null,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ]);
    }

    private function backfillRefund(object $payment, ?int $attemptId): void
    {
        if ($payment->status !== 'refunded') {
            return;
        }

        DB::table('payment_refunds')->insert([
            'payment_id' => $payment->id,
            'payment_attempt_id' => $attemptId,
            'provider' => $payment->provider ?? 'legacy',
            'provider_refund_reference' => null,
            'provider_status' => 'legacy_backfill',
            'kind' => 'full',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'reason' => 'Migrated from legacy payment status.',
            'occurred_at' => $payment->refunded_at,
            'received_at' => $payment->refunded_at ?? $payment->updated_at,
            'requires_review' => false,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ]);
    }

    private function attemptStatus(object $payment): string
    {
        return match ($payment->status) {
            'paid', 'refunded' => 'succeeded',
            'failed' => 'failed',
            'expired' => 'expired',
            default => $payment->checkout_url === null ? 'initialized' : 'checkout_created',
        };
    }
};
