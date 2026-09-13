<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_fee_enabled' => false,
            'boost_fee_enabled' => false,
            'listing_fee' => 3000,
            'boost_fee' => 3000,
            'boost_duration_hours' => null,
        ];
    }

    public function feesEnabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'listing_fee_enabled' => true,
            'boost_fee_enabled' => true,
        ]);
    }
}
