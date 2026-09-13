<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_active_categories_and_only_public_products(): void
    {
        $activeCategory = Category::factory()->create(['name' => 'Elektronik']);
        $inactiveCategory = Category::factory()->inactive()->create(['name' => 'Tidak Aktif']);
        $visible = Product::factory()->for($activeCategory)->create(['name' => 'Kamera Publik']);
        Product::factory()->for($activeCategory)->hidden()->create(['name' => 'Barang Hidden']);
        Product::factory()->for($activeCategory)->sold()->create(['name' => 'Barang Sold']);
        Product::factory()->for($activeCategory)->unpublished()->create(['name' => 'Belum Bayar']);
        Product::factory()->for($inactiveCategory)->create(['name' => 'Kategori Mati']);
        Product::factory()->for($activeCategory)->deleted()->create(['name' => 'Barang Dihapus']);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Elektronik')
            ->assertSee('Kamera Publik')
            ->assertSee(route('products.show', $visible), false)
            ->assertDontSee('Tidak Aktif')
            ->assertDontSee('Barang Hidden')
            ->assertDontSee('Barang Sold')
            ->assertDontSee('Belum Bayar')
            ->assertDontSee('Kategori Mati')
            ->assertDontSee('Barang Dihapus');
    }

    public function test_homepage_has_an_empty_state_without_public_products(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Belum ada listing aktif');
    }
}
