<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_cannot_access_seller_dashboard_or_products(): void
    {
        $seller = User::factory()->seller()->create([
            'suspended_until' => now()->addDays(7),
            'suspension_reason' => 'Pelanggaran ketentuan penjualan.',
        ]);

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('seller.products.index'))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('seller.products.create'))
            ->assertForbidden();
    }

    public function test_suspended_user_cannot_become_seller(): void
    {
        $buyer = User::factory()->create([
            'suspended_until' => now()->addDays(3),
            'suspension_reason' => 'Spamming.',
        ]);

        $this->actingAs($buyer)
            ->post(route('seller.activate'))
            ->assertForbidden();

        $this->assertTrue($buyer->refresh()->isBuyer());
    }

    public function test_suspended_user_cannot_submit_report(): void
    {
        $user = User::factory()->create([
            'suspended_until' => now()->addDays(5),
        ]);
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('products.reports.store', $product), [
                'reason' => 'spam',
                'description' => 'Laporan dari akun yang sedang disuspend.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('reports', [
            'reporter_id' => $user->id,
        ]);
    }

    public function test_active_user_can_access_normally(): void
    {
        $seller = User::factory()->seller()->create([
            'suspended_until' => null,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk();
    }
}
