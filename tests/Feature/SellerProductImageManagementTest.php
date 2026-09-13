<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_images_up_to_five_total(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        ProductImage::factory()->count(3)->for($product)->sequence(
            ['sort_order' => 0, 'is_primary' => true],
            ['sort_order' => 1],
            ['sort_order' => 2],
        )->create();

        $this->actingAs($seller)->post(route('seller.products.images.store', $product), [
            'images' => [
                $this->fakeImage('four.png'),
                $this->fakeImage('five.png'),
            ],
        ])->assertRedirect();

        $this->assertSame(5, $product->images()->count());
    }

    public function test_upload_rejects_more_than_five_images_and_non_image_files(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        ProductImage::factory()->count(5)->for($product)->create();

        $this->actingAs($seller)->post(route('seller.products.images.store', $product), [
            'images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors(['images', 'images.0']);

        $this->assertSame(5, $product->images()->count());
    }

    public function test_owner_can_make_an_image_primary_and_order_it_first(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $first = ProductImage::factory()->for($product)->primary()->create(['sort_order' => 0]);
        $second = ProductImage::factory()->for($product)->create(['sort_order' => 1]);

        $this->actingAs($seller)
            ->patch(route('seller.products.images.primary', [$product, $second]))
            ->assertRedirect();

        $this->assertFalse($first->refresh()->is_primary);
        $this->assertTrue($second->refresh()->is_primary);
        $this->assertSame(0, $second->sort_order);
        $this->assertSame(1, $first->sort_order);
    }

    public function test_deleting_primary_image_removes_file_and_promotes_next_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $primary = ProductImage::factory()->for($product)->primary()->create(['path' => 'products/primary.jpg', 'sort_order' => 0]);
        $next = ProductImage::factory()->for($product)->create(['path' => 'products/next.jpg', 'sort_order' => 1]);
        Storage::disk('public')->put($primary->path, 'image');
        Storage::disk('public')->put($next->path, 'image');

        $this->actingAs($seller)
            ->delete(route('seller.products.images.destroy', [$product, $primary]))
            ->assertRedirect();

        $this->assertModelMissing($primary);
        Storage::disk('public')->assertMissing($primary->path);
        $this->assertTrue($next->refresh()->is_primary);
        $this->assertSame(0, $next->sort_order);
    }

    public function test_seller_cannot_delete_the_only_remaining_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $image = ProductImage::factory()->for($product)->primary()->create(['path' => 'products/only.jpg']);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($seller)
            ->delete(route('seller.products.images.destroy', [$product, $image]))
            ->assertSessionHasErrors('images');

        $this->assertModelExists($image->refresh());
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_seller_cannot_manage_images_for_another_sellers_product(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->create();
        $image = ProductImage::factory()->for($product)->create();

        $this->actingAs($seller)
            ->patch(route('seller.products.images.primary', [$product, $image]))
            ->assertForbidden();

        $this->actingAs($seller)
            ->delete(route('seller.products.images.destroy', [$product, $image]))
            ->assertForbidden();
    }

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );
    }
}
