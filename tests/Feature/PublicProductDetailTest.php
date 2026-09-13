<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_is_accessible_by_slug_with_ordered_details(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kamera Film',
            'description' => 'Kamera analog terawat.',
            'location' => 'Jakarta',
            'price' => 800000,
        ]);
        ProductImage::factory()->for($product)->create(['path' => 'products/second.webp', 'sort_order' => 1]);
        ProductImage::factory()->for($product)->primary()->create(['path' => 'products/first.webp', 'sort_order' => 0]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Kamera Film')
            ->assertSee('Rp800.000')
            ->assertSee('Jakarta')
            ->assertSeeInOrder(['products/first.webp', 'products/second.webp']);
    }

    public function test_non_public_products_return_not_found(): void
    {
        $hidden = Product::factory()->hidden()->create();
        $sold = Product::factory()->sold()->create();
        $unpublished = Product::factory()->unpublished()->create();
        $future = Product::factory()->create(['published_at' => now()->addDay()]);
        $inactiveCategory = Category::factory()->inactive()->create();
        $inactive = Product::factory()->for($inactiveCategory)->create();

        foreach ([$hidden, $sold, $unpublished, $future, $inactive] as $product) {
            $this->get(route('products.show', $product))->assertNotFound();
        }
    }

    public function test_guest_can_open_safe_whatsapp_link_with_encoded_listing_message(): void
    {
        $seller = User::factory()->seller()->create(['whatsapp_number' => '6281234567890']);
        $product = Product::factory()->for($seller, 'seller')->create([
            'name' => 'Tas & Kamera / Edisi Baru?',
            'slug' => 'tas-kamera-edisi-baru',
        ]);
        $message = 'Halo, saya tertarik dengan barang Tas & Kamera / Edisi Baru? di Preloved IN. Apakah barangnya masih tersedia? '.route('products.show', $product);
        $expectedUrl = 'https://wa.me/6281234567890?text='.rawurlencode($message);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Chat Penjual')
            ->assertSee($expectedUrl, false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_missing_or_malformed_whatsapp_number_does_not_create_chat_link(): void
    {
        $missingNumberProduct = Product::factory()->create();
        $invalidSeller = User::factory()->seller()->create(['whatsapp_number' => 'javascript:alert(1)']);
        $invalidNumberProduct = Product::factory()->for($invalidSeller, 'seller')->create();

        $this->get(route('products.show', $missingNumberProduct))
            ->assertOk()
            ->assertSee('Kontak WhatsApp seller belum tersedia.')
            ->assertDontSee('https://wa.me/', false);

        $this->get(route('products.show', $invalidNumberProduct))
            ->assertOk()
            ->assertDontSee('https://wa.me/', false)
            ->assertDontSee('javascript:alert(1)', false);
    }

    public function test_product_content_is_escaped(): void
    {
        $product = Product::factory()->create([
            'name' => '<script>alert(1)</script>',
            'description' => '<img src=x onerror=alert(1)>',
            'location' => '<b>Bandung</b>',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<b>Bandung</b>', false);
    }
}
