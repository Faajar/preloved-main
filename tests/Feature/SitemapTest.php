<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml_with_public_pages_and_products(): void
    {
        $category = Category::factory()->create(['name' => 'Kamera Analog']);
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->for($category)->create([
            'name' => 'Canon AE-1 Program',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('home'));
        $response->assertSee(route('explore'));
        $response->assertSee(route('categories.index'));
        $response->assertSee(route('categories.show', $category));
        $response->assertSee(route('products.show', $product));
        $response->assertSee(route('sellers.show', $seller));
    }
}
