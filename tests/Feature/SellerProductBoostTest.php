<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerProductBoostTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_activate_free_boost_with_configured_duration(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        Setting::factory()->create(['id' => 1, 'boost_duration_hours' => 36]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();

        $this->actingAs($seller)
            ->post(route('seller.products.boosts.store', $product))
            ->assertRedirect();

        $boost = Boost::query()->sole();
        $this->assertSame($seller->id, $boost->user_id);
        $this->assertSame($product->id, $boost->product_id);
        $this->assertSame('2026-09-10 10:00:00', $boost->starts_at->toDateTimeString());
        $this->assertSame('2026-09-11 22:00:00', $boost->ends_at->toDateTimeString());
        $this->assertTrue($boost->isActive());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_duration_must_be_configured_and_fee_enabled_mode_creates_payment_without_activating_boost(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $settings = Setting::factory()->create(['id' => 1, 'boost_duration_hours' => null]);

        $this->actingAs($seller)->post(route('seller.products.boosts.store', $product))
            ->assertSessionHasErrors('boost');

        $settings->update(['boost_duration_hours' => 24, 'boost_fee_enabled' => true]);

        $this->actingAs($seller)->post(route('seller.products.boosts.store', $product))
            ->assertRedirect();

        $this->assertSame(0, Boost::query()->count());
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_only_owner_can_boost_and_listing_must_be_public(): void
    {
        Setting::factory()->create(['id' => 1, 'boost_duration_hours' => 24]);
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $hidden = Product::factory()->for($seller, 'seller')->hidden()->create();

        $this->actingAs($otherSeller)
            ->post(route('seller.products.boosts.store', $product))
            ->assertForbidden();

        $this->actingAs($seller)
            ->post(route('seller.products.boosts.store', $hidden))
            ->assertSessionHasErrors('boost');

        $this->assertSame(0, Boost::query()->count());
    }

    public function test_repeated_activation_is_idempotent_and_expired_boost_can_be_replaced(): void
    {
        $this->travelTo('2026-09-10 10:00:00');
        Setting::factory()->create(['id' => 1, 'boost_duration_hours' => 24]);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        Boost::factory()->for($product)->for($seller)->expired()->create();

        $this->actingAs($seller)->post(route('seller.products.boosts.store', $product))->assertRedirect();
        $this->actingAs($seller)->post(route('seller.products.boosts.store', $product))->assertRedirect();

        $this->assertSame(2, Boost::query()->count());
        $this->assertSame(1, Boost::query()->active()->count());
    }

    public function test_seller_can_have_only_one_effective_active_boost(): void
    {
        Setting::factory()->create(['id' => 1, 'boost_duration_hours' => 24]);
        $seller = User::factory()->seller()->create();
        $firstProduct = Product::factory()->for($seller, 'seller')->create();
        $secondProduct = Product::factory()->for($seller, 'seller')->create();
        Boost::factory()->for($firstProduct)->for($seller)->active()->create();

        $this->actingAs($seller)
            ->post(route('seller.products.boosts.store', $secondProduct))
            ->assertSessionHasErrors('boost');

        $this->assertSame(1, Boost::query()->count());
    }

    public function test_non_public_boost_does_not_block_another_listing(): void
    {
        Setting::factory()->create(['id' => 1, 'boost_duration_hours' => 24]);
        $seller = User::factory()->seller()->create();
        $hiddenProduct = Product::factory()->for($seller, 'seller')->hidden()->create();
        $publicProduct = Product::factory()->for($seller, 'seller')->create();
        Boost::factory()->for($hiddenProduct)->for($seller)->active()->create();

        $this->actingAs($seller)
            ->post(route('seller.products.boosts.store', $publicProduct))
            ->assertRedirect();

        $this->assertSame(2, Boost::query()->count());
        $this->assertSame(ProductStatus::Hidden, $hiddenProduct->status);
    }
}
