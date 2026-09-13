<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PaymentAttempt> */
class PaymentAttemptFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'sequence' => 1,
            'provider' => 'doku',
            'status' => PaymentAttemptStatus::Initialized,
            'provider_request_id' => (string) Str::uuid(),
            'invoice_number' => 'PIN'.strtoupper(Str::random(20)),
            'provider_reference' => null,
            'checkout_url' => null,
            'expires_at' => null,
            'succeeded_at' => null,
            'failed_at' => null,
            'expired_at' => null,
            'request_payload' => null,
            'request_body' => null,
            'request_payload_hash' => null,
            'request_timestamp' => null,
            'first_requested_at' => null,
            'last_reconciled_at' => null,
            'last_provider_status' => null,
            'request_count' => 0,
            'last_provider_error' => null,
        ];
    }

    public function checkoutCreated(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentAttemptStatus::CheckoutCreated,
            'provider_reference' => Str::random(32),
            'checkout_url' => 'https://sandbox.doku.com/checkout-link-v2/'.Str::random(40),
            'expires_at' => now()->addHour(),
            'request_count' => 1,
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentAttemptStatus::Succeeded,
            'succeeded_at' => now(),
        ]);
    }
}
