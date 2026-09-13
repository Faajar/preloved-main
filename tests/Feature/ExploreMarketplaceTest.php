<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\ProductCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExploreMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_search_matches_name_and_description(): void
    {
        Product::factory()->create(['name' => 'Kamera Analog', 'description' => 'Kondisi baik']);
        Product::factory()->create(['name' => 'Tas Harian', 'description' => 'Cocok untuk kamera']);
        Product::factory()->create(['name' => 'Sepatu Lari', 'description' => 'Ringan']);

        $this->get(route('explore', ['q' => 'kamera']))
            ->assertOk()
            ->assertSee('Kamera Analog')
            ->assertSee('Tas Harian')
            ->assertDontSee('Sepatu Lari');
    }

    public function test_category_price_condition_and_location_filters_are_combined(): void
    {
        $category = Category::factory()->create(['slug' => 'fashion']);
        $otherCategory = Category::factory()->create();
        Product::factory()->for($category)->create([
            'name' => 'Target Product',
            'price' => 150000,
            'condition' => ProductCondition::LikeNew,
            'location' => 'Bandung Kota',
        ]);
        Product::factory()->for($category)->create(['name' => 'Terlalu Mahal', 'price' => 400000]);
        Product::factory()->for($otherCategory)->create(['name' => 'Kategori Lain', 'price' => 150000]);

        $this->get(route('explore', [
            'category' => 'fashion',
            'min_price' => 100000,
            'max_price' => 200000,
            'condition' => ProductCondition::LikeNew->value,
            'location' => 'Bandung',
        ]))->assertOk()
            ->assertSee('Target Product')
            ->assertDontSee('Terlalu Mahal')
            ->assertDontSee('Kategori Lain');
    }

    public function test_price_sorting_uses_only_allowed_values(): void
    {
        Product::factory()->create(['name' => 'Murah', 'price' => 50000]);
        Product::factory()->create(['name' => 'Mahal', 'price' => 500000]);

        $this->get(route('explore', ['sort' => 'price_low']))
            ->assertOk()
            ->assertSeeInOrder(['Murah', 'Mahal']);

        $this->get(route('explore', ['sort' => 'price_high']))
            ->assertOk()
            ->assertSeeInOrder(['Mahal', 'Murah']);

        $this->get(route('explore', ['sort' => 'price desc; drop table products']))
            ->assertSessionHasErrors('sort');
    }

    public function test_invalid_price_range_is_rejected(): void
    {
        $this->get(route('explore', ['min_price' => 200000, 'max_price' => 100000]))
            ->assertSessionHasErrors('max_price');
    }

    public function test_explore_paginates_and_preserves_query_parameters(): void
    {
        Product::factory()->count(13)->create(['location' => 'Bandung']);

        $response = $this->get(route('explore', ['location' => 'Bandung']));

        $response->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->count() === 12 && $products->total() === 13)
            ->assertSee('location=Bandung', false);
    }
}
