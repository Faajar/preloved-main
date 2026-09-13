<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_only_authenticated_sellers_product_summaries(): void
    {
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        Product::factory()->for($seller, 'seller')->create();
        Product::factory()->for($seller, 'seller')->unpublished()->create();
        Product::factory()->for($seller, 'seller')->sold()->create();
        Product::factory()->for($seller, 'seller')->hidden()->create();
        Product::factory()->for($seller, 'seller')->deleted()->create();
        Product::factory()->for($otherSeller, 'seller')->count(2)->create();

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertViewHas('summary', [
                'active' => 1,
                'pending' => 1,
                'sold' => 1,
                'hidden' => 1,
                'boosted' => 0,
            ]);
    }

    public function test_dashboard_shows_empty_state_when_seller_has_no_products(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('Belum ada barang');
    }

    public function test_hidden_product_cannot_be_marked_sold_by_seller(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->hidden()->create();

        $this->actingAs($seller)
            ->patch(route('seller.products.sold', $product))
            ->assertForbidden();

        $this->assertSame(ProductStatus::Hidden, $product->refresh()->status);
    }
}
