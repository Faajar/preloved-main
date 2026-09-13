<?php

namespace Tests\Unit;

use App\Payments\DokuSignature;
use App\Payments\Gateways\DokuCheckoutGateway;
use Tests\TestCase;

class DokuCheckoutSignatureTest extends TestCase
{
    public function test_signature_uses_documented_components_and_raw_body(): void
    {
        config()->set('services.doku.secret_key', 'secret-key');
        $gateway = app(DokuCheckoutGateway::class);
        $body = '{"order":{"amount":3000}}';

        $signature = $gateway->signature(
            'MCH-001',
            'request-001',
            '2026-09-10T03:00:00Z',
            '/checkout/v1/payment',
            $body,
        );

        $digest = base64_encode(hash('sha256', $body, true));
        $components = "Client-Id:MCH-001\nRequest-Id:request-001\nRequest-Timestamp:2026-09-10T03:00:00Z\nRequest-Target:/checkout/v1/payment\nDigest:{$digest}";
        $expected = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, 'secret-key', true));

        $this->assertSame($expected, $signature);
        $this->assertNotSame($signature, $gateway->signature(
            'MCH-001',
            'request-001',
            '2026-09-10T03:00:00Z',
            '/checkout/v1/payment',
            "{$body}\n",
        ));
    }

    public function test_get_signature_omits_digest(): void
    {
        config()->set('services.doku.secret_key', 'secret-key');
        $signature = app(DokuSignature::class)->request(
            'MCH-001',
            'inquiry-001',
            '2026-09-10T03:00:00Z',
            '/orders/v1/status/PIN001',
        );
        $components = "Client-Id:MCH-001\nRequest-Id:inquiry-001\nRequest-Timestamp:2026-09-10T03:00:00Z\nRequest-Target:/orders/v1/status/PIN001";
        $expected = 'HMACSHA256='.base64_encode(hash_hmac('sha256', $components, 'secret-key', true));

        $this->assertSame($expected, $signature);
    }
}
