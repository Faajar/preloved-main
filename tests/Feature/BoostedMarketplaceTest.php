<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoostedMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_boost_gets_priority_and_visible_badge(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        $normal = Product::factory()->create(['name' => 'Produk Normal', 'published_at' => now()]);
        $boosted = Product::factory()->create(['name' => 'Produk Boosted', 'published_at' => now()->subDay()]);
        Boost::factory()->for($boosted)->for($boosted->seller)->active()->create();

        $this->get(route('explore'))
            ->assertOk()
            ->assertSeeInOrder(['Produk Boosted', 'Produk Normal'])
            ->assertSee('BOOSTED');

        $this->get(route('products.show', $boosted))
            ->assertOk()
            ->assertSee('BOOSTED');
    }

    public function test_scheduled_and_expired_boosts_get_no_badge_or_priority(): void
    {
        $older = Product::factory()->create(['name' => 'Produk Lama', 'published_at' => now()->subDay()]);
        $newer = Product::factory()->create(['name' => 'Produk Baru', 'published_at' => now()]);
        Boost::factory()->for($older)->for($older->seller)->scheduled()->create();
        Boost::factory()->for($older)->for($older->seller)->expired()->create();

        $this->get(route('explore'))
            ->assertOk()
            ->assertSeeInOrder(['Produk Baru', 'Produk Lama'])
            ->assertDontSee('BOOSTED');
    }

    public function test_boosted_products_from_different_sellers_use_oldest_boost_first(): void
    {
        $first = Product::factory()->create(['name' => 'Boost Pertama']);
        $second = Product::factory()->create(['name' => 'Boost Kedua']);
        Boost::factory()->for($first)->for($first->seller)->create([
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHour(),
        ]);
        Boost::factory()->for($second)->for($second->seller)->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $this->get(route('explore'))
            ->assertOk()
            ->assertSeeInOrder(['Boost Pertama', 'Boost Kedua']);
    }

    public function test_boost_priority_applies_to_home_category_and_seller_profile(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();
        $normal = Product::factory()->for($seller, 'seller')->for($category)->create(['name' => 'Normal Terbaru', 'published_at' => now()]);
        $boosted = Product::factory()->for($seller, 'seller')->for($category)->create(['name' => 'Boost Lama', 'published_at' => now()->subDay()]);
        Boost::factory()->for($boosted)->for($seller)->active()->create();

        foreach ([route('home'), route('categories.show', $category), route('sellers.show', $seller)] as $url) {
            $this->get($url)->assertOk()->assertSeeInOrder(['Boost Lama', 'Normal Terbaru']);
        }
    }

    public function test_price_sort_keeps_boosted_product_first(): void
    {
        $cheap = Product::factory()->create(['name' => 'Sangat Murah', 'price' => 10000]);
        $boosted = Product::factory()->create(['name' => 'Boost Mahal', 'price' => 500000]);
        Boost::factory()->for($boosted)->for($boosted->seller)->active()->create();

        $this->get(route('explore', ['sort' => 'price_low']))
            ->assertOk()
            ->assertSeeInOrder(['Boost Mahal', 'Sangat Murah']);
    }
}
