<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_activate_seller_role(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->post(route('seller.activate'))
            ->assertRedirect(route('seller.dashboard'));

        $this->assertSame(UserRole::Seller, $buyer->refresh()->role);
    }

    public function test_seller_keeps_access_to_buyer_dashboard(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Seller');
    }

    public function test_buyer_cannot_access_seller_area(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.dashboard'))
            ->assertForbidden();
    }

    public function test_seller_can_access_seller_area(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk();
    }

    public function test_non_admin_users_cannot_access_admin_area(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->seller()->create();

        $this->actingAs($buyer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_seller_and_admin_cannot_use_buyer_activation_endpoint(): void
    {
        $seller = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($seller)
            ->post(route('seller.activate'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('seller.activate'))
            ->assertForbidden();
    }
}
