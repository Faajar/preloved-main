<?php

namespace App\Payments\Data;

use Illuminate\Support\Carbon;

final readonly class VerifiedPaymentEvent
{
    public function __construct(
        public string $eventReference,
        public string $invoiceNumber,
        public int $amount,
        public string $status,
        public ?string $originalRequestId,
        public ?Carbon $occurredAt,
        public string $payloadHash,
        public ?int $refundAmount = null,
        public ?string $refundReference = null,
        public ?string $refundStatus = null,
        public ?string $refundType = null,
        public ?string $refundReason = null,
    ) {}
}
