<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_real_metrics_and_authorizes_only_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->seller()->create();

        Product::factory()->for($seller, 'seller')->create(['status' => ProductStatus::Available]);
        Report::factory()->create();
        Payment::factory()->for($seller)->paid()->create(['amount' => 3000]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($buyer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($seller)->get(route('admin.dashboard'))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard Admin');
        $response->assertSee('Rp3.000');
    }

    public function test_admin_can_update_monetization_settings_with_audit(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::factory()->create([
            'id' => 1,
            'listing_fee_enabled' => false,
            'boost_fee_enabled' => false,
            'listing_fee' => 3000,
            'boost_fee' => 3000,
            'boost_duration_hours' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Monetisasi & Boost');

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'listing_fee_enabled' => 1,
                'listing_fee' => 5000,
                'boost_fee_enabled' => 1,
                'boost_fee' => 4000,
                'boost_duration_hours' => 48,
                'reason' => 'Penyesuaian tarif promosi awal.',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $setting = Setting::query()->findOrFail(1);
        $this->assertTrue($setting->listing_fee_enabled);
        $this->assertSame(5000, $setting->listing_fee);
        $this->assertTrue($setting->boost_fee_enabled);
        $this->assertSame(4000, $setting->boost_fee);
        $this->assertSame(48, $setting->boost_duration_hours);

        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'setting.updated',
            'reason' => 'Penyesuaian tarif promosi awal.',
        ]);
    }

    public function test_admin_settings_validation_requires_reason_and_valid_ranges(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'listing_fee_enabled' => 0,
                'listing_fee' => -100,
                'boost_fee_enabled' => 0,
                'boost_fee' => -50,
                'boost_duration_hours' => 0,
                'reason' => '',
            ])
            ->assertSessionHasErrors(['listing_fee', 'boost_fee', 'boost_duration_hours', 'reason']);
    }

    public function test_admin_can_view_product_moderation_list_and_details(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Jaket Kulit Vintage']);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Jaket Kulit Vintage');

        $this->actingAs($admin)
            ->get(route('admin.products.show', $product->id))
            ->assertOk()
            ->assertSee('Jaket Kulit Vintage')
            ->assertSee('Informasi Seller');
    }

    public function test_admin_can_directly_hide_and_unhide_product_with_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['status' => ProductStatus::Available]);

        // 1. Hide product
        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product->id), [
                'status' => ProductStatus::Hidden->value,
                'reason' => 'Terindikasi barang replika tanpa izin.',
            ])
            ->assertRedirect(route('admin.products.show', $product->id));

        $this->assertSame(ProductStatus::Hidden, $product->refresh()->status);
        $this->get(route('products.show', $product))->assertNotFound();

        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'product.hidden',
            'subject_id' => $product->id,
            'reason' => 'Terindikasi barang replika tanpa izin.',
        ]);

        // 2. Unhide product
        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product->id), [
                'status' => ProductStatus::Available->value,
                'reason' => 'Klarifikasi seller valid, produk diaktifkan kembali.',
            ])
            ->assertRedirect(route('admin.products.show', $product->id));

        $this->assertSame(ProductStatus::Available, $product->refresh()->status);
        $this->get(route('products.show', $product))->assertOk();

        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'product.unhidden',
            'subject_id' => $product->id,
            'reason' => 'Klarifikasi seller valid, produk diaktifkan kembali.',
        ]);
    }

    public function test_admin_cannot_change_status_of_deleted_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $product->delete();

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product->id), [
                'status' => ProductStatus::Available->value,
                'reason' => 'Coba aktifkan produk terhapus.',
            ])
            ->assertSessionHasErrors('product');
    }

    public function test_admin_can_view_payment_monitoring_and_details(): void
    {
        $admin = User::factory()->admin()->create();
        $payment = Payment::factory()->paid()->create(['amount' => 3000]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Monitoring Pembayaran Fee')
            ->assertSee('Rp3.000');

        $this->actingAs($admin)
            ->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('Detail Transaksi')
            ->assertSee('Rp3.000');
    }
}
