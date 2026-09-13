<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_category_management(): void
    {
        $category = Category::factory()->create();

        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.categories.index'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.categories.edit', $category))->assertOk();
    }

    public function test_admin_can_create_category_with_generated_slug_and_audit(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => '  Barang Koleksi  ',
            'description' => 'Koleksi pilihan.',
            'reason' => 'Menambah kategori baru.',
        ])->assertRedirect();

        $category = Category::query()->where('name', 'Barang Koleksi')->firstOrFail();

        $this->assertSame('barang-koleksi', $category->slug);
        $this->assertTrue($category->is_active);
        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'category.created',
            'subject_type' => Category::class,
            'subject_id' => $category->id,
            'reason' => 'Menambah kategori baru.',
        ]);
    }

    public function test_admin_can_update_category_without_changing_slug_and_audit_change(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Fashion Lama', 'slug' => 'fashion-lama']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Fashion Baru',
            'description' => 'Deskripsi baru.',
            'reason' => 'Nama disesuaikan dengan katalog.',
        ])->assertRedirect(route('admin.categories.edit', $category));

        $category->refresh();
        $this->assertSame('Fashion Baru', $category->name);
        $this->assertSame('fashion-lama', $category->slug);
        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'category.updated',
            'subject_id' => $category->id,
        ]);
    }

    public function test_admin_can_deactivate_category_without_deleting_products(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $this->actingAs($admin)->patch(route('admin.categories.activation.update', $category), [
            'reason' => 'Kategori sedang ditinjau.',
        ])->assertRedirect(route('admin.categories.edit', $category));

        $this->assertFalse($category->refresh()->is_active);
        $this->assertModelExists($product->refresh());
        $this->assertDatabaseHas('moderation_audits', [
            'actor_id' => $admin->id,
            'action' => 'category.deactivated',
            'subject_id' => $category->id,
        ]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category), [
            'reason' => 'Penghapusan kategori.',
        ])->assertSessionHasErrors('category');

        $this->assertModelExists($category->refresh());
    }

    public function test_category_seeder_does_not_reactivate_category(): void
    {
        $this->seed(CategorySeeder::class);
        $category = Category::query()->where('slug', 'fashion')->firstOrFail();
        $category->update(['is_active' => false]);

        $this->seed(CategorySeeder::class);

        $this->assertFalse($category->refresh()->is_active);
    }
}
