<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\User;
use App\Payments\Exceptions\InvalidGatewayResponse;
use App\Payments\Gateways\DokuCheckoutGateway;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DokuCheckoutResponseVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_signed_response_is_accepted_without_session_id(): void
    {
        [$gateway, $attempt] = $this->gatewayAndAttempt();
        $expiresAt = now('Asia/Jakarta')->addHour()->format('YmdHis');
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => fn (Request $request) => $this->signedResponse($request, $attempt, [
                'response.order.session_id' => null,
                'response.payment.token_id' => 'token-as-reference',
                'response.payment.expired_date' => $expiresAt,
            ]),
        ]);

        $session = $gateway->createCheckout($attempt, $gateway->prepareCheckout($attempt));

        $this->assertSame('https://sandbox.doku.com/checkout-link-v2/token', $session->url);
        $this->assertSame('token-as-reference', $session->providerReference);
        $this->assertSame($expiresAt, $session->expiresAt->setTimezone('Asia/Jakarta')->format('YmdHis'));
    }

    public function test_optional_session_and_token_reference_may_both_be_absent(): void
    {
        [$gateway, $attempt] = $this->gatewayAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => fn (Request $request) => $this->signedResponse($request, $attempt, [
                'response.order.session_id' => null,
                'response.payment.token_id' => null,
            ]),
        ]);

        $session = $gateway->createCheckout($attempt, $gateway->prepareCheckout($attempt));

        $this->assertNull($session->providerReference);
    }

    #[DataProvider('invalidResponseCases')]
    public function test_untrusted_or_inconsistent_response_is_rejected(string $case): void
    {
        [$gateway, $attempt] = $this->gatewayAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => function (Request $request) use ($attempt, $case) {
                $overrides = match ($case) {
                    'invoice' => ['response.order.invoice_number' => 'PIN-WRONG'],
                    'amount' => ['response.order.amount' => '3001'],
                    'currency' => ['response.order.currency' => 'USD'],
                    'message' => ['message' => ['FAILED']],
                    'host' => ['response.payment.url' => 'https://evil.example/checkout'],
                    'scheme' => ['response.payment.url' => 'http://sandbox.doku.com/checkout'],
                    'expiry' => ['response.payment.expired_date' => 'not-a-date'],
                    default => [],
                };
                if ($case === 'client') {
                    return $this->signedResponse($request, $attempt, [], clientId: 'MCH-WRONG');
                }

                if ($case === 'request') {
                    return $this->signedResponse($request, $attempt, [], requestId: 'request-wrong');
                }

                if ($case === 'signature') {
                    return $this->signedResponse($request, $attempt, [], signature: 'HMACSHA256=invalid');
                }

                return $this->signedResponse($request, $attempt, $overrides);
            },
        ]);

        $this->expectException(InvalidGatewayResponse::class);

        $gateway->createCheckout($attempt, $gateway->prepareCheckout($attempt));
    }

    public function test_missing_signature_header_is_rejected_even_if_body_contains_signature_metadata(): void
    {
        [$gateway, $attempt] = $this->gatewayAndAttempt();
        Http::preventStrayRequests();
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => fn (Request $request) => $this->signedResponse(
                $request,
                $attempt,
                ['response.headers.signature' => 'HMACSHA256=body-only'],
                includeSignatureHeader: false,
            ),
        ]);

        $this->expectException(InvalidGatewayResponse::class);

        $gateway->createCheckout($attempt, $gateway->prepareCheckout($attempt));
    }

    public function test_malformed_json_response_is_rejected(): void
    {
        [$gateway, $attempt] = $this->gatewayAndAttempt();
        Http::preventStrayRequests();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => Http::response('{invalid', 200, [
                'Client-Id' => 'MCH-TEST',
                'Request-Id' => $attempt->provider_request_id,
                'Response-Timestamp' => $timestamp,
                'Signature' => $this->responseSignature($attempt->provider_request_id, $timestamp, '{invalid'),
            ]),
        ]);

        $this->expectException(InvalidGatewayResponse::class);

        $gateway->createCheckout($attempt, $gateway->prepareCheckout($attempt));
    }

    /** @return array<string, array{string}> */
    public static function invalidResponseCases(): array
    {
        return [
            'wrong client id' => ['client'],
            'wrong request id' => ['request'],
            'invalid signature' => ['signature'],
            'wrong invoice' => ['invoice'],
            'wrong amount' => ['amount'],
            'wrong currency' => ['currency'],
            'non success semantics' => ['message'],
            'unlisted host' => ['host'],
            'insecure scheme' => ['scheme'],
            'malformed expiry' => ['expiry'],
        ];
    }

    /** @return array{DokuCheckoutGateway, PaymentAttempt} */
    private function gatewayAndAttempt(): array
    {
        config()->set('services.doku.client_id', 'MCH-TEST');
        config()->set('services.doku.secret_key', 'secret');
        config()->set('services.doku.base_url', 'https://api-sandbox.doku.com');
        config()->set('services.doku.allowed_checkout_hosts', ['sandbox.doku.com']);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->create(['amount' => 3000]);
        $attempt = PaymentAttempt::factory()->for($payment)->create([
            'provider_request_id' => 'request-original',
            'invoice_number' => 'PIN260910ABC123',
        ]);

        return [app(DokuCheckoutGateway::class), $attempt->load('payment.user')];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function signedResponse(
        Request $request,
        PaymentAttempt $attempt,
        array $overrides = [],
        string $clientId = 'MCH-TEST',
        ?string $requestId = null,
        ?string $signature = null,
        bool $includeSignatureHeader = true,
    ): PromiseInterface {
        $payload = [
            'message' => ['SUCCESS'],
            'response' => [
                'order' => [
                    'amount' => '3000',
                    'invoice_number' => $attempt->invoice_number,
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

        foreach ($overrides as $path => $value) {
            data_set($payload, $path, $value);
        }

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $requestId ??= $request->header('Request-Id')[0];

        $headers = [
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Response-Timestamp' => $timestamp,
        ];

        if ($includeSignatureHeader) {
            $headers['Signature'] = $signature ?? $this->responseSignature($requestId, $timestamp, $rawBody, $clientId);
        }

        return Http::response($rawBody, 200, $headers);
    }

    private function responseSignature(
        string $requestId,
        string $timestamp,
        string $rawBody,
        string $clientId = 'MCH-TEST',
    ): string {
        $digest = base64_encode(hash('sha256', $rawBody, true));
        $components = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nResponse-Timestamp:{$timestamp}\nRequest-Target:/checkout/v1/payment\nDigest:{$digest}";

        return 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, 'secret', true));
    }
}
