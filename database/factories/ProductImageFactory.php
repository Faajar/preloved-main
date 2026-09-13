<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'disk' => 'public',
            'path' => 'products/'.fake()->uuid().'.webp',
            'alt_text' => fake()->optional()->sentence(4),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sort_order' => 0,
            'is_primary' => true,
        ]);
    }
}
