<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSellerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_profile_shows_only_public_products_without_private_contact_data(): void
    {
        $seller = User::factory()->seller()->create([
            'name' => 'Seller Aman',
            'email' => 'private@example.com',
            'whatsapp_number' => '6281234567890',
        ]);
        Product::factory()->for($seller, 'seller')->create(['name' => 'Barang Publik']);
        Product::factory()->for($seller, 'seller')->hidden()->create(['name' => 'Barang Hidden']);
        Product::factory()->for($seller, 'seller')->unpublished()->create(['name' => 'Belum Aktif']);

        $this->get(route('sellers.show', $seller))
            ->assertOk()
            ->assertSee('Seller Aman')
            ->assertSee('Barang Publik')
            ->assertDontSee('Barang Hidden')
            ->assertDontSee('Belum Aktif')
            ->assertDontSee('private@example.com')
            ->assertDontSee('6281234567890');
    }

    public function test_non_seller_profile_returns_not_found(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->get(route('sellers.show', $buyer))->assertNotFound();
        $this->get(route('sellers.show', $admin))->assertNotFound();
    }
}
