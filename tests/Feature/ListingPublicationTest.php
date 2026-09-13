<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\PaymentStatus;
use App\PaymentType;
use App\ProductCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_enabled_listing_is_unpublished_and_has_pending_payment(): void
    {
        Storage::fake('public');
        Setting::factory()->feesEnabled()->create(['id' => 1, 'listing_fee' => 4500]);
        $seller = User::factory()->seller()->create(['whatsapp_number' => '6281234567890']);
        $category = Category::factory()->create();

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'Sepatu Lari',
            'category_id' => $category->id,
            'price' => 350000,
            'condition' => ProductCondition::Good->value,
            'description' => 'Sepatu masih nyaman digunakan.',
            'location' => 'Surabaya',
            'images' => [$this->fakeImage('sepatu.png')],
        ])->assertRedirect();

        $product = Product::query()->sole();
        $payment = Payment::query()->sole();

        $this->assertNull($product->published_at);
        $this->assertSame($seller->id, $payment->user_id);
        $this->assertSame($product->id, $payment->product_id);
        $this->assertSame(PaymentType::Listing, $payment->type);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(4500, $payment->amount);
    }

    public function test_free_listing_is_published_without_payment_record(): void
    {
        Storage::fake('public');
        Setting::factory()->create(['id' => 1]);
        $seller = User::factory()->seller()->create(['whatsapp_number' => '6281234567890']);
        $category = Category::factory()->create();

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'Buku Bekas',
            'category_id' => $category->id,
            'price' => 50000,
            'condition' => ProductCondition::Fair->value,
            'description' => 'Buku lengkap dan masih terbaca jelas.',
            'location' => 'Yogyakarta',
            'images' => [$this->fakeImage('buku.png')],
        ])->assertRedirect();

        $this->assertNotNull(Product::query()->sole()->published_at);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_listing_creation_fails_clearly_when_platform_settings_are_missing(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create(['whatsapp_number' => '6281234567890']);
        $category = Category::factory()->create();

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'Tas Kanvas',
            'category_id' => $category->id,
            'price' => 100000,
            'condition' => ProductCondition::LikeNew->value,
            'description' => 'Tas bersih tanpa kerusakan.',
            'location' => 'Bogor',
            'images' => [$this->fakeImage('tas.png')],
        ])->assertNotFound();

        $this->assertSame(0, Product::query()->count());
    }

    private function fakeImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );
    }
}
