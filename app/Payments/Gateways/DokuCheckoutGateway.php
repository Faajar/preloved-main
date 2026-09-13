<?php

namespace App\Payments\Gateways;

use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\PaymentStatusResult;
use App\Payments\Data\PreparedCheckoutRequest;
use App\Payments\Data\VerifiedPaymentEvent;
use App\Payments\DokuCheckoutResponseVerifier;
use App\Payments\DokuSignature;
use App\Payments\DokuStatusResponseVerifier;
use App\Payments\Exceptions\AmbiguousPaymentAttempt;
use App\Payments\Exceptions\InvalidGatewayResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DokuCheckoutGateway implements PaymentGateway
{
    private const CHECKOUT_TARGET = '/checkout/v1/payment';

    public function __construct(
        private readonly DokuCheckoutResponseVerifier $responseVerifier,
        private readonly DokuStatusResponseVerifier $statusResponseVerifier,
        private readonly DokuSignature $dokuSignature,
    ) {}

    public function prepareCheckout(PaymentAttempt $attempt): PreparedCheckoutRequest
    {
        $payment = $attempt->payment;
        $payload = [
            'order' => [
                'amount' => $payment->amount,
                'invoice_number' => $attempt->invoice_number,
                'currency' => $payment->currency,
                'callback_url' => route('seller.payments.return', $payment),
                'callback_url_result' => route('seller.payments.return', $payment),
                'auto_redirect' => true,
                'line_items' => [[
                    'id' => strtoupper($payment->type->value),
                    'name' => $payment->type->label().' fee Preloved IN',
                    'quantity' => 1,
                    'price' => $payment->amount,
                    'category' => 'fee',
                ]],
            ],
            'payment' => [
                'payment_due_date' => (int) config('services.doku.payment_due_minutes'),
            ],
            'customer' => [
                'id' => (string) $payment->user_id,
                'name' => $payment->user->name,
                'email' => $payment->user->email,
            ],
            'additional_info' => [
                'override_notification_url' => route('payments.doku.notifications'),
            ],
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new PreparedCheckoutRequest($payload, $body, hash('sha256', $body));
    }

    public function createCheckout(PaymentAttempt $attempt, PreparedCheckoutRequest $request): CheckoutSession
    {
        $this->ensureConfigured();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $response = $this->client(
            $attempt->provider_request_id,
            $timestamp,
            self::CHECKOUT_TARGET,
            $request->body,
        )->withBody($request->body, 'application/json')->post(self::CHECKOUT_TARGET);

        if ($response->successful()) {
            return $this->responseVerifier->verify($response, $attempt, self::CHECKOUT_TARGET);
        }

        if ($response->status() === 409) {
            try {
                return $this->responseVerifier->verify($response, $attempt, self::CHECKOUT_TARGET);
            } catch (InvalidGatewayResponse $exception) {
                throw new AmbiguousPaymentAttempt('Replay checkout DOKU belum dapat dipastikan.', previous: $exception);
            }
        }

        $response->throw();

        throw new AmbiguousPaymentAttempt('Respons checkout DOKU tidak dapat dipastikan.');
    }

    public function checkStatus(PaymentAttempt $attempt): PaymentStatusResult
    {
        $this->ensureConfigured();
        $requestId = (string) Str::uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $requestTarget = '/orders/v1/status/'.rawurlencode((string) $attempt->invoice_number);
        $response = $this->client($requestId, $timestamp, $requestTarget)->get($requestTarget)->throw();

        return $this->statusResponseVerifier->verify($response, $attempt, $requestId, $requestTarget);
    }

    public function verifyNotification(Request $request): VerifiedPaymentEvent
    {
        $this->ensureConfigured();

        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $timestamp = $request->header('Request-Timestamp');
        $signature = $request->header('Signature');
        $rawBody = $request->getContent();

        if (strlen($rawBody) > 65536) {
            throw new RuntimeException('Ukuran payload notifikasi melebihi batas (maksimal 64 KB).');
        }

        if (strlen((string) $requestId) > 255 || strlen((string) $signature) > 512 || strlen((string) $clientId) > 128) {
            throw new RuntimeException('Panjang header notifikasi melebihi batas.');
        }

        if (! is_string($clientId) || ! hash_equals((string) config('services.doku.client_id'), $clientId)
            || ! is_string($requestId) || $requestId === ''
            || ! is_string($timestamp) || ! is_string($signature)) {
            throw new RuntimeException('Header notifikasi DOKU tidak valid.');
        }

        try {
            $sentAt = Carbon::parse($timestamp);
        } catch (Throwable) {
            throw new RuntimeException('Timestamp notifikasi DOKU tidak valid.');
        }

        if ($sentAt->diffInSeconds(now(), true) > (int) config('services.doku.webhook_tolerance_seconds')) {
            throw new RuntimeException('Notifikasi DOKU berada di luar batas waktu.');
        }

        $expectedSignature = $this->signature(
            $clientId,
            $requestId,
            $timestamp,
            '/'.$request->path(),
            $rawBody,
        );

        if (! hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('Signature notifikasi DOKU tidak valid.');
        }

        $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        $invoiceNumber = data_get($payload, 'order.invoice_number');
        $amount = data_get($payload, 'order.amount');
        $status = data_get($payload, 'transaction.status');
        $originalRequestId = data_get($payload, 'transaction.original_request_id');
        $transactionDate = data_get($payload, 'transaction.date');
        $refundAmount = data_get($payload, 'refund.amount');
        $refundReference = $this->firstString($payload, [
            'refund.id',
            'refund.reference',
            'refund.refund_id',
            'refund.refund_no',
            'refund.partner_refund_no',
            'refund.approval_code',
            'refund.request_id',
            'card_payment.request_id',
            'emoney_payment.refund_id',
            'additional_info.refund_no',
            'additional_info.refundNo',
            'additional_info.partnerRefundNo',
        ]);
        $refundStatus = data_get($payload, 'refund.status');
        $refundType = data_get($payload, 'refund.type', data_get($payload, 'transaction.type'));
        $refundReason = data_get($payload, 'refund.reason', data_get($payload, 'refund.message'));

        if (! is_string($invoiceNumber) || $invoiceNumber === ''
            || ! $this->isPositiveIntegerAmount($amount)
            || ! is_string($status) || $status === '') {
            throw new RuntimeException('Payload notifikasi DOKU tidak valid.');
        }

        if (strtoupper($status) === 'REFUNDED'
            && $refundAmount !== null
            && ! $this->isPositiveIntegerAmount($refundAmount)) {
            throw new RuntimeException('Nominal refund DOKU tidak valid.');
        }

        return new VerifiedPaymentEvent(
            $requestId,
            $invoiceNumber,
            (int) $amount,
            strtoupper($status),
            is_string($originalRequestId) ? $originalRequestId : null,
            is_string($transactionDate) ? Carbon::parse($transactionDate) : null,
            hash('sha256', $rawBody),
            $refundAmount === null ? null : (int) $refundAmount,
            $refundReference ?? (strtoupper($status) === 'REFUNDED' && is_string($originalRequestId)
                ? $originalRequestId
                : null),
            is_string($refundStatus) ? strtoupper($refundStatus) : null,
            is_string($refundType) ? strtoupper($refundType) : null,
            is_string($refundReason) ? $refundReason : null,
        );
    }

    public function signature(string $clientId, string $requestId, string $timestamp, string $requestTarget, string $rawBody): string
    {
        return $this->dokuSignature->request($clientId, $requestId, $timestamp, $requestTarget, $rawBody);
    }

    private function isPositiveIntegerAmount(mixed $amount): bool
    {
        if (is_int($amount)) {
            return $amount > 0;
        }

        return is_string($amount)
            && preg_match('/^[1-9]\d*$/D', $amount) === 1
            && filter_var($amount, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $paths
     */
    private function firstString(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function client(
        string $requestId,
        string $timestamp,
        string $requestTarget,
        ?string $rawBody = null,
    ): PendingRequest {
        return Http::baseUrl((string) config('services.doku.base_url'))
            ->connectTimeout((int) config('services.doku.connect_timeout_seconds', 5))
            ->timeout((int) config('services.doku.timeout_seconds', 15))
            ->acceptJson()
            ->withHeaders([
                'Client-Id' => (string) config('services.doku.client_id'),
                'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => $this->dokuSignature->request(
                    (string) config('services.doku.client_id'),
                    $requestId,
                    $timestamp,
                    $requestTarget,
                    $rawBody,
                ),
            ]);
    }

    private function ensureConfigured(): void
    {
        if (! is_string(config('services.doku.client_id')) || config('services.doku.client_id') === ''
            || ! is_string(config('services.doku.secret_key')) || config('services.doku.secret_key') === '') {
            throw new RuntimeException('Credential DOKU belum dikonfigurasi.');
        }

        $isProduction = app()->isProduction() || config('app.env') === 'production';

        if ($isProduction) {
            if (str_contains((string) config('services.doku.base_url'), 'sandbox')) {
                throw new RuntimeException('Mode produksi tidak boleh menggunakan endpoint DOKU sandbox.');
            }

            if (! str_starts_with((string) config('app.url'), 'https://')) {
                throw new RuntimeException('Mode produksi memerlukan URL aplikasi HTTPS.');
            }
        }
    }
}
