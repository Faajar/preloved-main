<?php

namespace App\Payments\Data;

final readonly class PreparedCheckoutRequest
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public array $payload,
        public string $body,
        public string $hash,
    ) {}
}
