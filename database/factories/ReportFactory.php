<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\ReportReason;
use App\ReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'product_id' => Product::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'description' => fake()->optional()->paragraph(),
            'status' => ReportStatus::Pending,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::Reviewed,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::Resolved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReportStatus::Rejected,
        ]);
    }
}
