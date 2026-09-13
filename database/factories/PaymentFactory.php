<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\User;
use App\PaymentStatus;
use App\PaymentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Payment $payment): void {
            $payment->fulfillment()->firstOrCreate([], [
                'status' => 'pending',
                'boost_duration_hours' => null,
            ]);
        });
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'type' => PaymentType::Listing,
            'amount' => 3000,
            'currency' => 'IDR',
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'failed_at' => null,
            'expired_at' => null,
            'refunded_at' => null,
            'metadata' => null,
        ];
    }

    public function forBoost(int $durationHours = 24): static
    {
        return $this->state(fn (): array => [
            'type' => PaymentType::Boost,
            'amount' => 3000,
        ])->afterCreating(function (Payment $payment) use ($durationHours): void {
            $payment->fulfillment()->updateOrCreate([], [
                'status' => 'pending',
                'boost_duration_hours' => $durationHours,
            ]);
        });
    }

    public function dokuCheckout(): static
    {
        return $this->afterCreating(function (Payment $payment): void {
            PaymentAttempt::factory()->for($payment)->checkoutCreated()->create();
        });
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
            'paid_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Expired,
            'expired_at' => now(),
            'paid_at' => null,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Refunded,
            'paid_at' => now()->subDay(),
            'refunded_at' => now(),
        ]);
    }
}
