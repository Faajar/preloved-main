<?php

namespace App\Payments;

use App\Models\PaymentAttempt;
use App\Payments\Data\CheckoutSession;
use App\Payments\Exceptions\InvalidGatewayResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use JsonException;
use Throwable;

class DokuCheckoutResponseVerifier
{
    public function __construct(private readonly DokuSignature $signature) {}

    public function verify(Response $response, PaymentAttempt $attempt, string $requestTarget): CheckoutSession
    {
        $rawBody = $response->body();
        $payload = $this->decode($rawBody);
        $clientId = $this->responseHeader($response, 'Client-Id');
        $requestId = $this->responseHeader($response, 'Request-Id');
        $timestamp = $this->responseHeader($response, 'Response-Timestamp');
        $signature = $this->responseHeader($response, 'Signature');

        $this->assertIdentity($clientId, $requestId, $attempt);
        $this->assertSignature($signature, $clientId, $requestId, $timestamp, $requestTarget, $rawBody);
        $this->assertSuccess($payload);

        $payment = $attempt->payment;
        $invoiceNumber = data_get($payload, 'response.order.invoice_number');
        $amount = data_get($payload, 'response.order.amount');
        $currency = data_get($payload, 'response.order.currency', 'IDR');
        $url = data_get($payload, 'response.payment.url');
        $providerReference = data_get($payload, 'response.order.session_id')
            ?? data_get($payload, 'response.payment.token_id');
        $expiredDate = data_get($payload, 'response.payment.expired_date');

        if (! is_string($invoiceNumber) || ! hash_equals((string) $attempt->invoice_number, $invoiceNumber)) {
            throw new InvalidGatewayResponse('Invoice pada respons DOKU tidak cocok.');
        }

        if (! is_numeric($amount) || (int) $amount !== $payment->amount) {
            throw new InvalidGatewayResponse('Nominal pada respons DOKU tidak cocok.');
        }

        if (! is_string($currency) || ! hash_equals(strtoupper($payment->currency), strtoupper($currency))) {
            throw new InvalidGatewayResponse('Mata uang pada respons DOKU tidak cocok.');
        }

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || ! $this->isAllowedCheckoutUrl($url)) {
            throw new InvalidGatewayResponse('URL checkout pada respons DOKU tidak diizinkan.');
        }

        if ($providerReference !== null && (! is_string($providerReference) || $providerReference === '')) {
            throw new InvalidGatewayResponse('Referensi checkout pada respons DOKU tidak valid.');
        }

        if (! is_string($expiredDate)) {
            throw new InvalidGatewayResponse('Waktu kedaluwarsa pada respons DOKU tidak valid.');
        }

        try {
            $expiresAt = Carbon::createFromFormat('!YmdHis', $expiredDate, 'Asia/Jakarta');
        } catch (Throwable) {
            throw new InvalidGatewayResponse('Waktu kedaluwarsa pada respons DOKU tidak valid.');
        }

        if ($expiresAt === false) {
            throw new InvalidGatewayResponse('Waktu kedaluwarsa pada respons DOKU tidak valid.');
        }

        return new CheckoutSession($url, $providerReference, $expiresAt->utc());
    }

    /** @return array<string, mixed> */
    private function decode(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidGatewayResponse('Respons DOKU bukan JSON yang valid.');
        }

        if (! is_array($payload)) {
            throw new InvalidGatewayResponse('Respons DOKU tidak valid.');
        }

        return $payload;
    }

    private function responseHeader(Response $response, string $header): string
    {
        $value = $response->header($header);

        if (! is_string($value) || $value === '') {
            throw new InvalidGatewayResponse("{$header} tidak tersedia pada respons DOKU.");
        }

        return $value;
    }

    private function assertIdentity(string $clientId, string $requestId, PaymentAttempt $attempt): void
    {
        if (! hash_equals((string) config('services.doku.client_id'), $clientId)) {
            throw new InvalidGatewayResponse('Client-Id pada respons DOKU tidak cocok.');
        }

        if (! hash_equals((string) $attempt->provider_request_id, $requestId)) {
            throw new InvalidGatewayResponse('Request-Id pada respons DOKU tidak cocok.');
        }
    }

    private function assertSignature(
        string $signature,
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        string $rawBody,
    ): void {
        $expected = $this->signature->response(
            $clientId,
            $requestId,
            $timestamp,
            $requestTarget,
            $rawBody,
        );

        if (! hash_equals($expected, $signature)) {
            throw new InvalidGatewayResponse('Signature respons DOKU tidak valid.');
        }
    }

    /** @param array<string, mixed> $payload */
    private function assertSuccess(array $payload): void
    {
        $messages = data_get($payload, 'message');

        if (! is_array($messages) || ! in_array('SUCCESS', $messages, true)) {
            throw new InvalidGatewayResponse('DOKU tidak mengembalikan respons checkout yang sukses.');
        }
    }

    private function isAllowedCheckoutUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $allowedHosts = config('services.doku.allowed_checkout_hosts', []);

        return $scheme === 'https'
            && is_string($host)
            && is_array($allowedHosts)
            && in_array(strtolower($host), array_map('strtolower', $allowedHosts), true);
    }
}
