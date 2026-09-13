<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentRefund;
use App\PaymentRefundKind;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PaymentRefund> */
class PaymentRefundFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'payment_attempt_id' => null,
            'provider' => 'doku',
            'provider_refund_reference' => 'REF'.strtoupper(Str::random(20)),
            'provider_status' => 'SUCCESS',
            'kind' => PaymentRefundKind::Full,
            'amount' => 3000,
            'currency' => 'IDR',
            'reason' => null,
            'occurred_at' => now(),
            'received_at' => now(),
            'requires_review' => false,
        ];
    }

    public function forAttempt(PaymentAttempt $attempt): static
    {
        return $this->state(fn (): array => [
            'payment_id' => $attempt->payment_id,
            'payment_attempt_id' => $attempt->id,
        ]);
    }
}
