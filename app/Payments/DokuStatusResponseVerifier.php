<?php

namespace App\Payments;

use App\Models\PaymentAttempt;
use App\Payments\Data\PaymentStatusResult;
use App\Payments\Exceptions\InvalidGatewayResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use JsonException;
use Throwable;

class DokuStatusResponseVerifier
{
    public function __construct(private readonly DokuSignature $signature) {}

    public function verify(
        Response $response,
        PaymentAttempt $attempt,
        string $inquiryRequestId,
        string $requestTarget,
    ): PaymentStatusResult {
        $rawBody = $response->body();
        $payload = $this->decode($rawBody);
        $clientId = $this->header($response, 'Client-Id');
        $responseRequestId = $this->header($response, 'Request-Id');
        $timestamp = $this->header($response, 'Response-Timestamp');
        $signature = $this->header($response, 'Signature');

        if (! hash_equals((string) config('services.doku.client_id'), $clientId)
            || ! hash_equals($inquiryRequestId, $responseRequestId)) {
            throw new InvalidGatewayResponse('Identitas respons Check Status DOKU tidak cocok.');
        }

        $expectedSignature = $this->signature->response(
            $clientId,
            $responseRequestId,
            $timestamp,
            $requestTarget,
        );

        if (! hash_equals($expectedSignature, $signature)) {
            throw new InvalidGatewayResponse('Signature respons Check Status DOKU tidak valid.');
        }

        $invoiceNumber = data_get($payload, 'order.invoice_number');
        $amount = data_get($payload, 'order.amount');
        $status = data_get($payload, 'transaction.status');
        $orderStatus = data_get($payload, 'order.status');
        $originalRequestId = data_get($payload, 'transaction.original_request_id');
        $occurredAt = data_get($payload, 'transaction.date');

        if (! is_string($invoiceNumber) || ! hash_equals((string) $attempt->invoice_number, $invoiceNumber)
            || ! is_numeric($amount) || (int) $amount !== $attempt->payment->amount
            || ! is_string($status) || $status === '') {
            throw new InvalidGatewayResponse('Payload respons Check Status DOKU tidak cocok.');
        }

        if (is_string($originalRequestId)
            && ! hash_equals((string) $attempt->provider_request_id, $originalRequestId)) {
            throw new InvalidGatewayResponse('Original Request-Id respons Check Status DOKU tidak cocok.');
        }

        try {
            $transactionDate = is_string($occurredAt) ? Carbon::parse($occurredAt) : null;
        } catch (Throwable) {
            throw new InvalidGatewayResponse('Tanggal respons Check Status DOKU tidak valid.');
        }

        return new PaymentStatusResult(
            $invoiceNumber,
            (int) $amount,
            strtoupper($status),
            is_string($orderStatus) ? strtoupper($orderStatus) : null,
            is_string($originalRequestId) ? $originalRequestId : null,
            $transactionDate,
            hash('sha256', $rawBody),
        );
    }

    /** @return array<string, mixed> */
    private function decode(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidGatewayResponse('Respons Check Status DOKU bukan JSON yang valid.');
        }

        if (! is_array($payload)) {
            throw new InvalidGatewayResponse('Respons Check Status DOKU tidak valid.');
        }

        return $payload;
    }

    private function header(Response $response, string $name): string
    {
        $value = $response->header($name);

        if (! is_string($value) || $value === '') {
            throw new InvalidGatewayResponse("{$name} tidak tersedia pada respons Check Status DOKU.");
        }

        return $value;
    }
}
