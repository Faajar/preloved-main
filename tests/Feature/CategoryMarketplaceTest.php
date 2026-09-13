<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_category_shows_only_its_public_products(): void
    {
        $category = Category::factory()->create(['name' => 'Buku']);
        $otherCategory = Category::factory()->create();
        Product::factory()->for($category)->create(['name' => 'Novel Publik']);
        Product::factory()->for($category)->hidden()->create(['name' => 'Novel Hidden']);
        Product::factory()->for($otherCategory)->create(['name' => 'Kategori Lain']);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee('Novel Publik')
            ->assertDontSee('Novel Hidden')
            ->assertDontSee('Kategori Lain');
    }

    public function test_category_index_shows_active_categories_and_public_listing_counts(): void
    {
        $activeCategory = Category::factory()->create(['name' => 'Buku']);
        $inactiveCategory = Category::factory()->inactive()->create(['name' => 'Tidak Aktif']);
        Product::factory()->for($activeCategory)->create();
        Product::factory()->for($inactiveCategory)->create();

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Buku')
            ->assertSee('1 listing')
            ->assertDontSee('Tidak Aktif');
    }

    public function test_inactive_category_returns_not_found(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->get(route('categories.show', $category))->assertNotFound();
    }

    public function test_explore_rejects_inactive_category_filter(): void
    {
        $category = Category::factory()->inactive()->create(['slug' => 'tidak-aktif']);

        $this->get(route('explore', ['category' => $category->slug]))
            ->assertSessionHasErrors(['category']);
    }
}
