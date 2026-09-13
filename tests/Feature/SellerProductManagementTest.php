<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\ProductCondition;
use App\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_a_free_active_listing_with_images_and_whatsapp_profile(): void
    {
        Storage::fake('public');
        Setting::factory()->create(['id' => 1]);
        $seller = User::factory()->seller()->create(['whatsapp_number' => null]);
        $category = Category::factory()->create();

        $response = $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'Kamera Analog',
            'category_id' => $category->id,
            'price' => 750000,
            'condition' => ProductCondition::Good->value,
            'description' => 'Kamera terawat dan berfungsi baik.',
            'location' => 'Bandung',
            'whatsapp_number' => '081234567890',
            'images' => [$this->fakeImage('kamera.png')],
            'seller_id' => User::factory()->create()->id,
            'status' => ProductStatus::Hidden->value,
        ]);

        $product = Product::query()->sole();

        $response->assertRedirect(route('seller.products.edit', $product));
        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame(ProductStatus::Available, $product->status);
        $this->assertNotNull($product->published_at);
        $this->assertSame(750000, $product->price);
        $this->assertSame('6281234567890', $seller->refresh()->whatsapp_number);
        $this->assertCount(1, $product->images);
        $this->assertTrue($product->images->first()->is_primary);
        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    public function test_whatsapp_input_is_normalized_to_canonical_international_digits(): void
    {
        Storage::fake('public');
        Setting::factory()->create(['id' => 1]);
        $category = Category::factory()->create();

        foreach ([
            'local' => [' 081234567890 ', '6281234567890'],
            'international' => ['6281234567890', '6281234567890'],
            'plus international' => ['+6281234567890', '6281234567890'],
        ] as [$input, $expected]) {
            $seller = User::factory()->seller()->create(['whatsapp_number' => null]);

            $this->actingAs($seller)->post(route('seller.products.store'), [
                'name' => 'Barang '.$expected,
                'category_id' => $category->id,
                'price' => 100000,
                'condition' => ProductCondition::Good->value,
                'description' => 'Deskripsi barang yang valid.',
                'location' => 'Bandung',
                'whatsapp_number' => $input,
                'images' => [$this->fakeImage('barang.png')],
            ])->assertRedirect();

            $this->assertSame($expected, $seller->refresh()->whatsapp_number);
        }
    }

    public function test_invalid_whatsapp_input_does_not_create_product_or_update_seller(): void
    {
        Storage::fake('public');
        Setting::factory()->create(['id' => 1]);
        $seller = User::factory()->seller()->create(['whatsapp_number' => null]);
        $category = Category::factory()->create();

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'Barang Invalid',
            'category_id' => $category->id,
            'price' => 100000,
            'condition' => ProductCondition::Good->value,
            'description' => 'Deskripsi barang yang valid.',
            'location' => 'Bandung',
            'whatsapp_number' => '62 812-3456-7890<script>',
            'images' => [$this->fakeImage('barang.png')],
        ])->assertSessionHasErrors('whatsapp_number');

        $this->assertNull($seller->refresh()->whatsapp_number);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_buyer_cannot_access_seller_product_routes(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.products.create'))
            ->assertForbidden();
    }

    public function test_product_creation_requires_valid_fields_whatsapp_and_image(): void
    {
        Setting::factory()->create(['id' => 1]);
        $seller = User::factory()->seller()->create(['whatsapp_number' => null]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [])
            ->assertSessionHasErrors(['name', 'category_id', 'price', 'condition', 'description', 'location', 'whatsapp_number', 'images']);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_seller_can_update_only_owned_product_fields(): void
    {
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $category = Category::factory()->create();

        $this->actingAs($seller)->put(route('seller.products.update', $product), [
            'name' => 'Nama Baru',
            'category_id' => $category->id,
            'price' => 200000,
            'condition' => ProductCondition::LikeNew->value,
            'description' => 'Deskripsi baru yang valid.',
            'location' => 'Jakarta',
            'seller_id' => $otherSeller->id,
            'status' => ProductStatus::Hidden->value,
            'published_at' => null,
        ])->assertRedirect(route('seller.products.edit', $product));

        $product->refresh();
        $this->assertSame('Nama Baru', $product->name);
        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame(ProductStatus::Available, $product->status);
        $this->assertNotNull($product->published_at);
    }

    public function test_seller_cannot_edit_or_delete_another_sellers_product(): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->create();

        $this->actingAs($seller)->get(route('seller.products.edit', $product))->assertForbidden();
        $this->actingAs($seller)->delete(route('seller.products.destroy', $product))->assertForbidden();
        $this->assertNotSoftDeleted($product);
    }

    public function test_owner_can_mark_product_sold_and_soft_delete_it_without_removing_images(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $image = $product->images()->create([
            'disk' => 'public',
            'path' => 'products/test/image.jpg',
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($seller)
            ->patch(route('seller.products.sold', $product))
            ->assertRedirect();

        $this->assertSame(ProductStatus::Sold, $product->refresh()->status);

        $this->actingAs($seller)
            ->delete(route('seller.products.destroy', $product))
            ->assertRedirect(route('seller.products.index'));

        $this->assertSoftDeleted($product);
        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );
    }
}
