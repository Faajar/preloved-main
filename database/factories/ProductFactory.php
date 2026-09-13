<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\ProductCondition;
use App\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'seller_id' => User::factory()->seller(),
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->numberBetween(10, 5000) * 1000,
            'condition' => fake()->randomElement(ProductCondition::cases()),
            'location' => fake()->city(),
            'status' => ProductStatus::Available,
            'published_at' => now(),
        ];
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Sold,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Hidden,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => null,
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now(),
        ]);
    }
}
