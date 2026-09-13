<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookEvent;
use App\Models\Product;
use App\Models\User;
use App\PaymentAttemptStatus;
use App\Payments\Gateways\DokuCheckoutGateway;
use App\PaymentStatus;
use App\PaymentWebhookProcessingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokuPaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_success_notification_publishes_listing_once(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->unpublished()->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $attempt = $this->dokuAttempt($payment);

        $body = $this->notificationBody($payment, $attempt, 'SUCCESS');
        $headers = $this->signedHeaders('event-1', $body);

        $this->call('POST', route('payments.doku.notifications'), [], [], [], $headers, $body)->assertOk();
        $this->call('POST', route('payments.doku.notifications'), [], [], [], $headers, $body)->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertNotNull($payment->fulfillment->fulfilled_at);
        $this->assertNotNull($product->refresh()->published_at);
        $this->assertSame(1, PaymentWebhookEvent::query()->count());
    }

    public function test_invalid_signature_or_amount_does_not_change_payment(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->unpublished()->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $attempt = $this->dokuAttempt($payment);
        $body = $this->notificationBody($payment, $attempt, 'SUCCESS');
        $headers = $this->signedHeaders('event-invalid', $body);
        $headers['HTTP_SIGNATURE'] = 'HMACSHA256=invalid';

        $this->call('POST', route('payments.doku.notifications'), [], [], [], $headers, $body)->assertBadRequest();
        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);

        $wrongAmountBody = $this->notificationBody($payment, $attempt, 'SUCCESS', $payment->amount + 1);
        $this->call('POST', route('payments.doku.notifications'), [], [], [], $this->signedHeaders('event-amount', $wrongAmountBody), $wrongAmountBody)
            ->assertBadRequest();
        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
    }

    public function test_verified_success_creates_one_paid_boost_and_refund_stops_it(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->forBoost(24)->create();
        $attempt = $this->dokuAttempt($payment);

        $successBody = $this->notificationBody($payment, $attempt, 'SUCCESS');
        $this->call('POST', route('payments.doku.notifications'), [], [], [], $this->signedHeaders('event-success', $successBody), $successBody)
            ->assertOk();

        $boost = Boost::query()->sole();
        $this->assertSame($payment->id, $boost->payment_id);
        $this->assertTrue($boost->isActive());

        $refundBody = $this->notificationBody($payment, $attempt, 'REFUNDED');
        $this->call('POST', route('payments.doku.notifications'), [], [], [], $this->signedHeaders('event-refund', $refundBody), $refundBody)
            ->assertOk();

        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertFalse($boost->refresh()->isActive());
    }

    public function test_timeout_notification_keeps_payment_pending_for_reconciliation(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $attempt = $this->dokuAttempt($payment);
        $body = $this->notificationBody($payment, $attempt, 'TIMEOUT');

        $this->call('POST', route('payments.doku.notifications'), [], [], [], $this->signedHeaders('event-timeout', $body), $body)
            ->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
        $this->assertSame(PaymentAttemptStatus::Ambiguous, $attempt->refresh()->status);
    }

    public function test_unknown_status_is_preserved_as_unhandled(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $attempt = $this->dokuAttempt($payment);
        $body = $this->notificationBody($payment, $attempt, 'FUTURE_STATUS');

        $this->call('POST', route('payments.doku.notifications'), [], [], [], $this->signedHeaders('event-future', $body), $body)
            ->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
        $this->assertSame(PaymentWebhookProcessingStatus::Unhandled, PaymentWebhookEvent::query()->sole()->processing_status);
    }

    public function test_failed_channel_notification_does_not_fail_checkout(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $attempt = $this->dokuAttempt($payment);
        $body = $this->notificationBody($payment, $attempt, 'FAILED');

        $this->call('POST', route('payments.doku.notifications'), [], [], [], $this->signedHeaders('event-failed', $body), $body)
            ->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
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

    private function notificationBody(Payment $payment, PaymentAttempt $attempt, string $status, ?int $amount = null): string
    {
        return json_encode([
            'transaction' => [
                'status' => $status,
                'date' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                'original_request_id' => $attempt->provider_request_id,
            ],
            'order' => [
                'invoice_number' => $attempt->invoice_number,
                'amount' => $amount ?? $payment->amount,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    private function signedHeaders(string $eventId, string $body): array
    {
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
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
