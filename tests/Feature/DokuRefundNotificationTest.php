<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhookEvent;
use App\Models\Product;
use App\Models\User;
use App\PaymentRefundKind;
use App\Payments\Gateways\DokuCheckoutGateway;
use App\PaymentStatus;
use App\PaymentWebhookProcessingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokuRefundNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_refund_uses_refund_fields_and_does_not_require_checkout_request_id(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->forBoost(24)->paid()->create([
            'amount' => 3000,
        ]);
        $attempt = $this->dokuAttempt($payment);
        $payment->fulfillment()->update([
            'status' => 'fulfilled',
            'boost_duration_hours' => 24,
            'fulfilled_at' => now()->subHour(),
            'attempt_count' => 1,
        ]);
        $boost = Boost::factory()->for($payment)->for($product)->for($seller)->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(23),
        ]);
        $body = $this->refundNotificationBody($payment, 'BO-34204', 3000, 'FULL_REFUND', 'Customer request');

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-full', $body),
            $body,
        )->assertOk();

        $refund = PaymentRefund::query()->sole();
        $this->assertSame('BO-34204', $refund->provider_refund_reference);
        $this->assertSame(3000, $refund->amount);
        $this->assertSame(PaymentRefundKind::Full, $refund->kind);
        $this->assertSame('Customer request', $refund->reason);
        $this->assertFalse($refund->requires_review);
        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertFalse($boost->refresh()->isActive());
    }

    public function test_official_card_refund_shape_uses_order_amount_and_back_office_reference(): void
    {
        $this->travelTo('2026-05-25 03:05:24');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create([
            'amount' => 1001,
        ]);
        $this->dokuAttempt($payment);
        $payment->fulfillment()->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now()->subHour(),
            'attempt_count' => 1,
        ]);
        $body = json_encode([
            'order' => [
                'invoice_number' => $payment->currentAttempt->invoice_number,
                'amount' => 1001,
            ],
            'customer' => [
                'name' => 'Anton Budiman',
                'email' => 'anton@doku.com',
            ],
            'transaction' => [
                'status' => 'REFUNDED',
                'date' => '2026-05-25T03:05:24Z',
                'original_request_id' => 'BO-34204',
            ],
            'service' => ['id' => 'CREDIT_CARD'],
            'acquirer' => ['id' => 'BANK_MANDIRI'],
            'channel' => ['id' => 'CREDIT_CARD'],
            'card_payment' => [
                'masked_card_number' => '455633******8800',
                'approval_code' => '078786',
                'response_code' => '00',
                'response_message' => 'Transaction Has Been Successfully Refund',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-official-card-refund', $body),
            $body,
        )->assertOk();

        $refund = PaymentRefund::query()->sole();
        $this->assertSame('BO-34204', $refund->provider_refund_reference);
        $this->assertSame(1001, $refund->amount);
        $this->assertSame(PaymentRefundKind::Full, $refund->kind);
        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertNull($product->refresh()->published_at);
    }

    public function test_refund_with_unknown_or_failed_provider_status_is_not_applied(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $body = $this->refundNotificationBody($payment, 'BO-FAILED-1', 3000, 'FULL_REFUND', 'Provider rejected');
        $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        data_set($payload, 'refund.status', 'FAILED');
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-failed', $body),
            $body,
        )->assertOk();

        $this->assertSame(0, PaymentRefund::query()->count());
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertSame(PaymentWebhookProcessingStatus::Unhandled, PaymentWebhookEvent::query()->sole()->processing_status);
    }

    public function test_unknown_refund_type_is_preserved_for_review_without_reversing_entitlement(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $body = $this->refundNotificationBody($payment, 'BO-UNKNOWN-TYPE-1', 3000, 'FUTURE_REFUND_TYPE', 'Unknown type');

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-unknown-type', $body),
            $body,
        )->assertOk();

        $this->assertSame(0, PaymentRefund::query()->count());
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertSame(PaymentWebhookProcessingStatus::Unhandled, PaymentWebhookEvent::query()->sole()->processing_status);
    }

    public function test_fractional_refund_amount_is_rejected_instead_of_truncated(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $body = $this->refundNotificationBody($payment, 'BO-FRACTION-1', 1000, 'PARTIAL_REFUND', 'Fraction test');
        $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        data_set($payload, 'refund.amount', '1000.5');
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-fraction', $body),
            $body,
        )->assertBadRequest();

        $this->assertSame(0, PaymentRefund::query()->count());
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
    }

    public function test_partial_refund_is_recorded_for_review_without_reversing_entitlement(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->forBoost(24)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $payment->fulfillment()->update([
            'status' => 'fulfilled',
            'boost_duration_hours' => 24,
            'fulfilled_at' => now()->subHour(),
            'attempt_count' => 1,
        ]);
        $boost = Boost::factory()->for($payment)->for($product)->for($seller)->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(23),
        ]);
        $body = $this->refundNotificationBody($payment, 'BO-PARTIAL-1', 1000, 'PARTIAL_REFUND', 'Partial adjustment');

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-partial', $body),
            $body,
        )->assertOk();

        $refund = PaymentRefund::query()->sole();
        $this->assertSame(1000, $refund->amount);
        $this->assertSame(PaymentRefundKind::Partial, $refund->kind);
        $this->assertTrue($refund->requires_review);
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertTrue($boost->refresh()->isActive());
        $this->assertSame(PaymentWebhookProcessingStatus::Processed, PaymentWebhookEvent::query()->sole()->processing_status);
    }

    public function test_duplicate_refund_notification_with_new_event_id_does_not_duplicate_refund(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $body = $this->refundNotificationBody($payment, 'BO-DUPLICATE-1', 1000, 'PARTIAL_REFUND', 'Duplicate test');

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-first', $body),
            $body,
        )->assertOk();
        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-redelivery', $body),
            $body,
        )->assertOk();

        $this->assertSame(1, PaymentRefund::query()->count());
        $this->assertSame(2, PaymentWebhookEvent::query()->count());
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
    }

    public function test_duplicate_refund_reference_with_different_amount_is_rejected(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $firstBody = $this->refundNotificationBody($payment, 'BO-CONFLICT-1', 1000, 'PARTIAL_REFUND', 'First amount');
        $conflictingBody = $this->refundNotificationBody($payment, 'BO-CONFLICT-1', 1500, 'PARTIAL_REFUND', 'Conflicting amount');

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-original', $firstBody),
            $firstBody,
        )->assertOk();
        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-refund-conflict', $conflictingBody),
            $conflictingBody,
        )->assertBadRequest();

        $this->assertSame(1, PaymentRefund::query()->count());
        $this->assertSame(1000, PaymentRefund::query()->sole()->amount);
        $this->assertSame(1, PaymentWebhookEvent::query()->count());
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
    }

    public function test_multiple_partial_refunds_reverse_entitlement_only_when_total_reaches_payment_amount(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->forBoost(24)->paid()->create([
            'amount' => 3000,
        ]);
        $this->dokuAttempt($payment);
        $payment->fulfillment()->update([
            'status' => 'fulfilled',
            'boost_duration_hours' => 24,
            'fulfilled_at' => now()->subHour(),
            'attempt_count' => 1,
        ]);
        $boost = Boost::factory()->for($payment)->for($product)->for($seller)->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(23),
        ]);
        $firstBody = $this->refundNotificationBody($payment, 'BO-PARTIAL-FIRST', 1000, 'PARTIAL_REFUND', 'First refund');
        $finalBody = $this->refundNotificationBody($payment, 'BO-PARTIAL-FINAL', 2000, 'PARTIAL_REFUND', 'Final refund');

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-partial-first', $firstBody),
            $firstBody,
        )->assertOk();
        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertTrue($boost->refresh()->isActive());

        $this->call(
            'POST',
            route('payments.doku.notifications'),
            [],
            [],
            [],
            $this->signedHeaders('event-partial-final', $finalBody),
            $finalBody,
        )->assertOk();

        $this->assertSame(2, PaymentRefund::query()->count());
        $finalRefund = PaymentRefund::query()->latest('id')->firstOrFail();
        $this->assertSame(PaymentRefundKind::Partial, $finalRefund->kind);
        $this->assertTrue($finalRefund->requires_review);
        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertFalse($boost->refresh()->isActive());
    }

    private function dokuAttempt(Payment $payment): PaymentAttempt
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.webhook_tolerance_seconds', 300);

        return PaymentAttempt::factory()->for($payment)->create([
            'provider_request_id' => 'request-original',
            'invoice_number' => 'PIN260910ABC123',
        ]);
    }

    private function refundNotificationBody(
        Payment $payment,
        string $refundReference,
        int $refundAmount,
        string $refundType,
        string $reason,
    ): string {
        return json_encode([
            'order' => [
                'invoice_number' => $payment->currentAttempt->invoice_number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ],
            'transaction' => [
                'status' => 'REFUNDED',
                'type' => $refundType,
                'date' => now('UTC')->format('Y-m-d\\TH:i:s\\Z'),
                'original_request_id' => $refundReference,
            ],
            'refund' => [
                'amount' => $refundAmount,
                'status' => 'SUCCESS',
                'type' => $refundType,
                'reason' => $reason,
                'approval_code' => $refundReference,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    private function signedHeaders(string $eventId, string $body): array
    {
        $timestamp = now('UTC')->format('Y-m-d\\TH:i:s\\Z');
        $signature = app(DokuCheckoutGateway::class)->signature(
            'MCH-TEST',
            $eventId,
            $timestamp,
            '/payments/doku/notifications',
            $body,
        );

        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CLIENT_ID' => 'MCH-TEST',
            'HTTP_REQUEST_ID' => $eventId,
            'HTTP_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_SIGNATURE' => $signature,
        ];
    }
}
