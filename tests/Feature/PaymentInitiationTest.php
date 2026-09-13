<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Payments\PaymentInitiator;
use App\PaymentStatus;
use App\PaymentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_doku_checkout_using_platform_fee_not_product_price(): void
    {
        $this->freezeTime(fn () => null);
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => function (Request $request) {
                $payload = [
                    'message' => ['SUCCESS'],
                    'response' => [
                        'order' => [
                            'amount' => '3000',
                            'invoice_number' => $request['order']['invoice_number'],
                            'currency' => 'IDR',
                            'session_id' => 'session-123',
                        ],
                        'payment' => [
                            'token_id' => 'token-123',
                            'url' => 'https://sandbox.doku.com/checkout-link-v2/token',
                            'expired_date' => now('Asia/Jakarta')->addHour()->format('YmdHis'),
                        ],
                    ],
                ];
                $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

                return Http::response($rawBody, 200, [
                    'Client-Id' => 'MCH-TEST',
                    'Request-Id' => $request->header('Request-Id')[0],
                    'Response-Timestamp' => $timestamp,
                    'Signature' => $this->responseSignature(
                        'MCH-TEST',
                        $request->header('Request-Id')[0],
                        $timestamp,
                        $rawBody,
                    ),
                ]);
            },
        ]);

        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create(['price' => 999000]);
        $payment = Payment::factory()->for($seller)->for($product)->create([
            'amount' => 3000,
        ]);

        $this->actingAs($seller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertRedirect('https://sandbox.doku.com/checkout-link-v2/token');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api-sandbox.doku.com/checkout/v1/payment'
                && $request['order']['amount'] === 3000
                && $request['order']['line_items'][0]['price'] === 3000
                && $request['order']['amount'] !== 999000
                && $request->hasHeader('Signature')
                && $request->hasHeader('Request-Id');
        });

        $payment->refresh()->load('currentAttempt');
        $this->assertSame('doku', $payment->currentAttempt->provider);
        $this->assertNotNull($payment->currentAttempt->provider_request_id);
        $this->assertNotNull($payment->currentAttempt->invoice_number);
        $this->assertTrue($payment->hasUsableCheckout());
    }

    public function test_invalid_doku_response_is_not_persisted_as_checkout(): void
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => Http::response([
                'message' => ['SUCCESS'],
                'response' => [
                    'order' => [
                        'amount' => '3000',
                        'invoice_number' => 'untrusted',
                        'currency' => 'IDR',
                    ],
                    'payment' => [
                        'url' => 'https://evil.example/checkout',
                        'expired_date' => now('Asia/Jakarta')->addHour()->format('YmdHis'),
                    ],
                ],
            ]),
        ]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();

        $this->actingAs($seller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $attempt = $payment->attempts()->sole();
        $this->assertNull($attempt->checkout_url);
        $this->assertNull($attempt->provider_reference);
        $this->assertSame(1, $attempt->request_count);
        $this->assertNotNull($attempt->request_body);
        $this->assertSame(hash('sha256', $attempt->request_body), $attempt->request_payload_hash);
    }

    public function test_retry_reuses_exact_request_body_and_request_id(): void
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        $sentBodies = [];
        $sentRequestIds = [];
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => function (Request $request) use (&$sentBodies, &$sentRequestIds) {
                $sentBodies[] = $request->body();
                $sentRequestIds[] = $request->header('Request-Id')[0];

                if (count($sentBodies) === 1) {
                    return Http::response(['error_messages' => ['temporary response']], 500);
                }

                $payload = [
                    'message' => ['SUCCESS'],
                    'response' => [
                        'order' => [
                            'amount' => '3000',
                            'invoice_number' => $request['order']['invoice_number'],
                            'currency' => 'IDR',
                        ],
                        'payment' => [
                            'url' => 'https://sandbox.doku.com/checkout-link-v2/replayed',
                            'expired_date' => now('Asia/Jakarta')->addHour()->format('YmdHis'),
                        ],
                    ],
                ];
                $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

                return Http::response($rawBody, 409, [
                    'Client-Id' => 'MCH-TEST',
                    'Request-Id' => $request->header('Request-Id')[0],
                    'Response-Timestamp' => $timestamp,
                    'Signature' => $this->responseSignature('MCH-TEST', $request->header('Request-Id')[0], $timestamp, $rawBody),
                ]);
            },
            'https://api-sandbox.doku.com/orders/v1/status/*' => Http::response([], 500),
        ]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();

        $this->actingAs($seller)->post(route('seller.payments.checkout', $payment))->assertRedirect();
        $seller->update(['name' => 'Nama Berubah Setelah Request']);
        $this->actingAs($seller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertRedirect('https://sandbox.doku.com/checkout-link-v2/replayed');

        $this->assertCount(2, $sentBodies);
        $this->assertSame($sentBodies[0], $sentBodies[1]);
        $this->assertSame($sentRequestIds[0], $sentRequestIds[1]);
        $this->assertSame(2, $payment->currentAttempt->request_count);
    }

    public function test_seller_can_replace_expired_payment_with_new_attempt_and_checkout(): void
    {
        $this->freezeTime(fn () => null);
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => function (Request $request) {
                $payload = [
                    'message' => ['SUCCESS'],
                    'response' => [
                        'order' => [
                            'amount' => '3000',
                            'invoice_number' => $request['order']['invoice_number'],
                            'currency' => 'IDR',
                        ],
                        'payment' => [
                            'url' => 'https://sandbox.doku.com/checkout-link-v2/replacement',
                            'expired_date' => now('Asia/Jakarta')->addHour()->format('YmdHis'),
                        ],
                    ],
                ];
                $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $timestamp = now('UTC')->format('Y-m-d\\TH:i:s\\Z');

                return Http::response($rawBody, 200, [
                    'Client-Id' => 'MCH-TEST',
                    'Request-Id' => $request->header('Request-Id')[0],
                    'Response-Timestamp' => $timestamp,
                    'Signature' => $this->responseSignature('MCH-TEST', $request->header('Request-Id')[0], $timestamp, $rawBody),
                ]);
            },
        ]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->expired()->create();
        $oldAttempt = $payment->attempts()->create([
            'sequence' => 1,
            'provider' => 'doku',
            'status' => 'expired',
            'provider_request_id' => 'expired-request-id',
            'invoice_number' => 'PIN-EXPIRED-001',
            'expires_at' => now()->subMinute(),
            'expired_at' => now()->subMinute(),
        ]);

        $this->actingAs($seller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertRedirect('https://sandbox.doku.com/checkout-link-v2/replacement');

        $payment->refresh()->load('attempts', 'currentAttempt');
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertCount(2, $payment->attempts);
        $this->assertSame($oldAttempt->id, $payment->attempts->sortBy('sequence')->first()->id);
        $this->assertSame(2, $payment->currentAttempt->sequence);
        $this->assertNotSame('expired-request-id', $payment->currentAttempt->provider_request_id);
        $this->assertNotSame('PIN-EXPIRED-001', $payment->currentAttempt->invoice_number);
        $this->assertTrue($payment->hasUsableCheckout());
    }

    public function test_late_response_from_superseded_attempt_does_not_become_usable_checkout(): void
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $oldAttempt = $payment->attempts()->create([
            'sequence' => 1,
            'provider' => 'doku',
            'status' => 'ambiguous',
            'provider_request_id' => 'old-in-flight-request',
            'invoice_number' => 'PIN-OLD-IN-FLIGHT',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => function (Request $request) use ($oldAttempt, $payment) {
                $oldAttempt->update([
                    'status' => 'expired',
                    'expired_at' => now(),
                ]);
                $payment->attempts()->create([
                    'sequence' => 2,
                    'provider' => 'doku',
                    'status' => 'checkout_created',
                    'provider_request_id' => 'new-current-request',
                    'invoice_number' => 'PIN-NEW-CURRENT',
                    'checkout_url' => 'https://sandbox.doku.com/checkout-link-v2/current',
                    'expires_at' => now()->addHour(),
                ]);
                $payload = [
                    'message' => ['SUCCESS'],
                    'response' => [
                        'order' => [
                            'amount' => '3000',
                            'invoice_number' => $oldAttempt->invoice_number,
                            'currency' => 'IDR',
                        ],
                        'payment' => [
                            'url' => 'https://sandbox.doku.com/checkout-link-v2/stale',
                            'expired_date' => now('Asia/Jakarta')->addHour()->format('YmdHis'),
                        ],
                    ],
                ];
                $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $timestamp = now('UTC')->format('Y-m-d\\TH:i:s\\Z');

                return Http::response($rawBody, 200, [
                    'Client-Id' => 'MCH-TEST',
                    'Request-Id' => $request->header('Request-Id')[0],
                    'Response-Timestamp' => $timestamp,
                    'Signature' => $this->responseSignature('MCH-TEST', $request->header('Request-Id')[0], $timestamp, $rawBody),
                ]);
            },
        ]);

        $result = app(PaymentInitiator::class)->initiate($payment);

        $this->assertSame(2, $result->currentAttempt->sequence);
        $this->assertSame('https://sandbox.doku.com/checkout-link-v2/current', $result->currentAttempt->checkout_url);
        $this->assertNull($oldAttempt->refresh()->checkout_url);
        $this->assertSame('expired', $oldAttempt->status->value);
    }

    public function test_seller_can_replace_failed_payment_with_new_attempt(): void
    {
        $this->freezeTime(fn () => null);
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => function (Request $request) {
                $payload = [
                    'message' => ['SUCCESS'],
                    'response' => [
                        'order' => [
                            'amount' => '3000',
                            'invoice_number' => $request['order']['invoice_number'],
                            'currency' => 'IDR',
                        ],
                        'payment' => [
                            'url' => 'https://sandbox.doku.com/checkout-link-v2/failed-replacement',
                            'expired_date' => now('Asia/Jakarta')->addHour()->format('YmdHis'),
                        ],
                    ],
                ];
                $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $timestamp = now('UTC')->format('Y-m-d\\TH:i:s\\Z');

                return Http::response($rawBody, 200, [
                    'Client-Id' => 'MCH-TEST',
                    'Request-Id' => $request->header('Request-Id')[0],
                    'Response-Timestamp' => $timestamp,
                    'Signature' => $this->responseSignature('MCH-TEST', $request->header('Request-Id')[0], $timestamp, $rawBody),
                ]);
            },
        ]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->failed()->create();
        $payment->attempts()->create([
            'sequence' => 1,
            'provider' => 'doku',
            'status' => 'failed',
            'provider_request_id' => 'failed-request-id',
            'invoice_number' => 'PIN-FAILED-001',
            'failed_at' => now()->subMinute(),
        ]);

        $this->actingAs($seller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertRedirect('https://sandbox.doku.com/checkout-link-v2/failed-replacement');

        $payment->refresh()->load('attempts', 'currentAttempt');
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertNull($payment->failed_at);
        $this->assertCount(2, $payment->attempts);
        $this->assertSame(2, $payment->currentAttempt->sequence);
        $this->assertTrue($payment->hasUsableCheckout());
    }

    public function test_existing_checkout_is_reused_and_other_seller_cannot_open_it(): void
    {
        Http::preventStrayRequests();
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->dokuCheckout()->create();

        $this->actingAs($seller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertRedirect($payment->currentAttempt->checkout_url);
        Http::assertNothingSent();

        $this->actingAs($otherSeller)
            ->post(route('seller.payments.checkout', $payment))
            ->assertForbidden();
    }

    private function responseSignature(string $clientId, string $requestId, string $timestamp, string $rawBody): string
    {
        $digest = base64_encode(hash('sha256', $rawBody, true));
        $components = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nResponse-Timestamp:{$timestamp}\nRequest-Target:/checkout/v1/payment\nDigest:{$digest}";

        return 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, 'secret', true));
    }

    public function test_paid_boost_creates_pending_payment_with_fee_and_duration_snapshot(): void
    {
        Setting::factory()->create([
            'id' => 1,
            'boost_fee_enabled' => true,
            'boost_fee' => 4500,
            'boost_duration_hours' => 36,
        ]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();

        $this->actingAs($seller)
            ->post(route('seller.products.boosts.store', $product))
            ->assertRedirect();

        $payment = Payment::query()->sole();
        $this->assertSame(PaymentType::Boost, $payment->type);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(4500, $payment->amount);
        $this->assertSame(36, $payment->fulfillment->boost_duration_hours);
        $this->assertSame(0, $product->boosts()->count());
    }
}
