<?php

namespace App\Payments\Data;

use Illuminate\Support\Carbon;

final readonly class CheckoutSession
{
    public function __construct(
        public string $url,
        public ?string $providerReference,
        public Carbon $expiresAt,
    ) {}
}
