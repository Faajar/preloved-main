<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerPaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_sees_only_their_own_payment_history_and_detail(): void
    {
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create(['name' => 'Kamera Seller']);
        $otherProduct = Product::factory()->for($otherSeller, 'seller')->create(['name' => 'Barang Seller Lain']);
        $payment = Payment::factory()->for($seller)->for($product)->create();
        $otherPayment = Payment::factory()->for($otherSeller)->for($otherProduct)->create();

        $this->actingAs($seller)->get(route('seller.payments.index'))
            ->assertOk()
            ->assertSee('Kamera Seller')
            ->assertDontSee('Barang Seller Lain');

        $this->actingAs($seller)->get(route('seller.payments.show', $payment))
            ->assertOk()
            ->assertSee('Kamera Seller');

        $this->actingAs($seller)->get(route('seller.payments.show', $otherPayment))->assertForbidden();
    }

    public function test_expired_payment_detail_offers_a_new_payment_attempt(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $payment = Payment::factory()->for($seller)->for($product)->expired()->create();

        $this->actingAs($seller)->get(route('seller.payments.show', $payment))
            ->assertOk()
            ->assertSee('Buat percobaan pembayaran baru')
            ->assertSee(route('seller.payments.checkout', $payment), false);
    }

    public function test_browser_return_is_informational_and_does_not_mark_payment_paid(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->unpublished()->create();
        $payment = Payment::factory()->for($seller)->for($product)->create();

        $this->actingAs($seller)->get(route('seller.payments.return', $payment))
            ->assertOk()
            ->assertSee('menunggu dan memverifikasi notifikasi pembayaran langsung dari DOKU');

        $this->assertNull($product->refresh()->published_at);
        $this->assertSame('pending', $payment->refresh()->status->value);
    }
}
