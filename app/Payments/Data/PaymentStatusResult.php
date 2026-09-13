<?php

namespace App\Payments\Data;

use Illuminate\Support\Carbon;

final readonly class PaymentStatusResult
{
    public function __construct(
        public string $invoiceNumber,
        public int $amount,
        public string $status,
        public ?string $orderStatus,
        public ?string $originalRequestId,
        public ?Carbon $occurredAt,
        public string $payloadHash,
    ) {}
}
