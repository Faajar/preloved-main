<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\User;
use App\PaymentAttemptStatus;
use App\PaymentFulfillmentStatus;
use App\Payments\DokuSignature;
use App\PaymentStatus;
use App\PaymentType;
use App\ProductStatus;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReconcilePaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reconciles_stale_attempts_and_fulfills_unfulfilled_paid_payments(): void
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');

        Http::preventStrayRequests();

        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create([
            'status' => ProductStatus::Available,
            'published_at' => null,
        ]);

        // 1. Stale attempt with SUCCESS response from DOKU
        $payment1 = Payment::factory()->for($seller)->create([
            'product_id' => $product->id,
            'type' => PaymentType::Listing,
            'status' => PaymentStatus::Pending,
            'amount' => 3000,
        ]);

        $attempt1 = PaymentAttempt::factory()->for($payment1)->create([
            'status' => PaymentAttemptStatus::CheckoutCreated,
            'invoice_number' => 'INV-REC-001',
            'provider_request_id' => 'REQ-CHECKOUT-001',
            'expires_at' => now()->subMinutes(5),
            'sequence' => 1,
        ]);

        // 2. Paid payment that has not been fulfilled yet
        $payment2 = Payment::factory()->for($seller)->paid()->create([
            'product_id' => $product->id,
            'type' => PaymentType::Listing,
            'amount' => 3000,
        ]);
        $payment2->fulfillment()->update(['status' => PaymentFulfillmentStatus::Pending]);

        Http::fake([
            'https://api-sandbox.doku.com/orders/v1/status/*' => fn (Request $request) => $this->signedStatusResponse($request, $attempt1, 'SUCCESS'),
        ]);

        $this->artisan('payments:reconcile')
            ->expectsOutputToContain('Rekonsiliasi selesai')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Paid, $payment1->refresh()->status);
        $this->assertSame(PaymentFulfillmentStatus::Fulfilled, $payment2->refresh()->fulfillment->status);
        $this->assertNotNull($product->refresh()->published_at);
    }

    private function signedStatusResponse(Request $request, PaymentAttempt $attempt, string $status): PromiseInterface
    {
        $payload = [
            'order' => [
                'invoice_number' => $attempt->invoice_number,
                'amount' => $attempt->payment->amount,
            ],
            'transaction' => [
                'status' => $status,
                'date' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                'original_request_id' => $attempt->provider_request_id,
            ],
        ];

        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $requestId = $request->header('Request-Id')[0] ?? '';
        $target = '/orders/v1/status/'.rawurlencode((string) $attempt->invoice_number);

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
