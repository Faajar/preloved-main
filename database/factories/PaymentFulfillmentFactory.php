<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentFulfillment;
use App\PaymentFulfillmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentFulfillment> */
class PaymentFulfillmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'status' => PaymentFulfillmentStatus::Pending,
            'boost_duration_hours' => null,
            'fulfilled_at' => null,
            'reversed_at' => null,
            'last_failure' => null,
            'attempt_count' => 0,
            'next_retry_at' => null,
        ];
    }

    public function fulfilled(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentFulfillmentStatus::Fulfilled,
            'fulfilled_at' => now(),
            'attempt_count' => 1,
        ]);
    }
}
