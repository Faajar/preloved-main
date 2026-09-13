<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Setting;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_creates_the_initial_database_driven_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(8, Category::query()->count());
        $this->assertSame(
            ['Aksesoris', 'Buku', 'Elektronik', 'Fashion', 'Hobi', 'Lainnya', 'Rumah Tangga', 'Sepatu'],
            Category::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_category_seeder_is_idempotent(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(8, Category::query()->count());
    }

    public function test_category_seeder_preserves_existing_category_description(): void
    {
        $this->seed(CategorySeeder::class);
        $category = Category::query()->where('slug', 'fashion')->firstOrFail();
        $category->update(['description' => 'Deskripsi yang dikelola admin.']);

        $this->seed(CategorySeeder::class);

        $this->assertSame('Deskripsi yang dikelola admin.', $category->refresh()->description);
    }

    public function test_setting_seeder_creates_one_free_launch_configuration(): void
    {
        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $setting = Setting::query()->sole();

        $this->assertFalse($setting->listing_fee_enabled);
        $this->assertFalse($setting->boost_fee_enabled);
        $this->assertSame(3000, $setting->listing_fee);
        $this->assertSame(3000, $setting->boost_fee);
        $this->assertNull($setting->boost_duration_hours);
    }
}
