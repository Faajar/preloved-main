<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\User;
use App\PaymentAttemptStatus;
use App\Payments\DokuSignature;
use App\Payments\Exceptions\InvalidGatewayResponse;
use App\Payments\PaymentReconciler;
use App\PaymentStatus;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DokuPaymentStatusCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_success_status_marks_payment_paid_and_fulfills_listing(): void
    {
        [$payment, $attempt] = $this->paymentAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/orders/v1/status/*' => fn (Request $request) => $this->signedStatusResponse($request, $attempt, 'SUCCESS'),
        ]);

        app(PaymentReconciler::class)->reconcile($attempt);

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertSame(PaymentAttemptStatus::Succeeded, $attempt->refresh()->status);
        $this->assertNotNull($payment->fulfillment->fulfilled_at);
        $this->assertNotNull($payment->product->refresh()->published_at);
        Http::assertSent(function (Request $request): bool {
            $signature = $request->header('Signature')[0] ?? '';

            return $request->method() === 'GET'
                && $request->url() === 'https://api-sandbox.doku.com/orders/v1/status/PIN-STATUS-001'
                && $signature !== '';
        });
    }

    public function test_timeout_status_remains_pending_and_ambiguous(): void
    {
        [$payment, $attempt] = $this->paymentAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/orders/v1/status/*' => fn (Request $request) => $this->signedStatusResponse($request, $attempt, 'TIMEOUT'),
        ]);

        app(PaymentReconciler::class)->reconcile($attempt);

        $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
        $this->assertSame(PaymentAttemptStatus::Ambiguous, $attempt->refresh()->status);
        $this->assertSame('TIMEOUT', $attempt->last_provider_status);
    }

    public function test_expired_status_is_terminal_for_pending_payment(): void
    {
        [$payment, $attempt] = $this->paymentAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/orders/v1/status/*' => fn (Request $request) => $this->signedStatusResponse($request, $attempt, 'EXPIRED'),
        ]);

        app(PaymentReconciler::class)->reconcile($attempt);

        $this->assertSame(PaymentStatus::Expired, $payment->refresh()->status);
        $this->assertSame(PaymentAttemptStatus::Expired, $attempt->refresh()->status);
    }

    public function test_status_response_with_wrong_original_request_id_is_rejected(): void
    {
        [$payment, $attempt] = $this->paymentAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/orders/v1/status/*' => fn (Request $request) => $this->signedStatusResponse(
                $request,
                $attempt,
                'SUCCESS',
                originalRequestId: 'wrong-request',
            ),
        ]);

        try {
            app(PaymentReconciler::class)->reconcile($attempt);
            $this->fail('Expected invalid provider response.');
        } catch (InvalidGatewayResponse) {
            $this->assertSame(PaymentStatus::Pending, $payment->refresh()->status);
            $this->assertSame(PaymentAttemptStatus::Ambiguous, $attempt->refresh()->status);
            $this->assertNotNull($attempt->last_provider_error);
        }
    }

    /** @return array{Payment, PaymentAttempt} */
    private function paymentAndAttempt(): array
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->unpublished()->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $attempt = PaymentAttempt::factory()->for($payment)->create([
            'status' => PaymentAttemptStatus::Ambiguous,
            'provider_request_id' => 'checkout-request-001',
            'invoice_number' => 'PIN-STATUS-001',
        ]);

        return [$payment, $attempt->load('payment.user')];
    }

    private function signedStatusResponse(
        Request $request,
        PaymentAttempt $attempt,
        string $status,
        ?string $originalRequestId = null,
    ): PromiseInterface {
        $payload = [
            'order' => [
                'invoice_number' => $attempt->invoice_number,
                'amount' => $attempt->payment->amount,
            ],
            'transaction' => [
                'status' => $status,
                'date' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                'original_request_id' => $originalRequestId ?? $attempt->provider_request_id,
            ],
        ];
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $requestId = $request->header('Request-Id')[0];
        $target = '/orders/v1/status/'.rawurlencode($attempt->invoice_number);

        return Http::response($payload, 200, [
            'Client-Id' => 'MCH-TEST',
            'Request-Id' => $requestId,
            'Response-Timestamp' => $timestamp,
            'Signature' => app(DokuSignature::class)->response(
                'MCH-TEST',
                $requestId,
                $timestamp,
                $target,
            ),
        ]);
    }
}
