<?php

namespace App\Payments;

class DokuSignature
{
    public function request(
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        ?string $rawBody = null,
    ): string {
        return $this->calculate(
            'Request-Timestamp',
            $clientId,
            $requestId,
            $timestamp,
            $requestTarget,
            $rawBody,
        );
    }

    public function response(
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        ?string $rawBody = null,
    ): string {
        return $this->calculate(
            'Response-Timestamp',
            $clientId,
            $requestId,
            $timestamp,
            $requestTarget,
            $rawBody,
        );
    }

    private function calculate(
        string $timestampHeader,
        string $clientId,
        string $requestId,
        string $timestamp,
        string $requestTarget,
        ?string $rawBody,
    ): string {
        $components = [
            "Client-Id:{$clientId}",
            "Request-Id:{$requestId}",
            "{$timestampHeader}:{$timestamp}",
            "Request-Target:{$requestTarget}",
        ];

        if ($rawBody !== null) {
            $components[] = 'Digest:'.base64_encode(hash('sha256', $rawBody, true));
        }

        return 'HMACSHA256='.base64_encode(hash_hmac(
            'sha256',
            implode("\n", $components),
            (string) config('services.doku.secret_key'),
            true,
        ));
    }
}
