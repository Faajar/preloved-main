<?php

namespace Tests\Feature;

use App\Models\Boost;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\PaymentStatus;
use App\PaymentType;
use App\ProductCondition;
use App\ProductStatus;
use App\ReportReason;
use App\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_two_tables_have_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('categories', ['name', 'slug', 'description', 'is_active']));
        $this->assertTrue(Schema::hasColumns('products', ['seller_id', 'category_id', 'name', 'slug', 'description', 'price', 'condition', 'location', 'status', 'published_at', 'deleted_at']));
        $this->assertTrue(Schema::hasColumns('product_images', ['product_id', 'disk', 'path', 'sort_order', 'is_primary']));
        $this->assertTrue(Schema::hasColumns('boosts', ['product_id', 'user_id', 'starts_at', 'ends_at']));
        $this->assertTrue(Schema::hasColumns('payments', ['user_id', 'product_id', 'type', 'amount', 'currency', 'status', 'paid_at', 'metadata']));
        $this->assertTrue(Schema::hasColumns('reports', ['reporter_id', 'product_id', 'reason', 'description', 'status']));
        $this->assertTrue(Schema::hasColumns('settings', ['listing_fee_enabled', 'boost_fee_enabled', 'listing_fee', 'boost_fee', 'boost_duration_hours']));
        $this->assertTrue(Schema::hasColumn('users', 'whatsapp_number'));
    }

    public function test_product_factory_creates_cast_attributes_and_relationships(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($seller, 'seller')->for($category)->create([
            'price' => 125000,
            'condition' => ProductCondition::LikeNew,
        ]);
        $image = ProductImage::factory()->for($product)->primary()->create();

        $this->assertSame(ProductStatus::Available, $product->status);
        $this->assertSame(ProductCondition::LikeNew, $product->condition);
        $this->assertSame(125000, $product->price);
        $this->assertTrue($product->published_at->isToday());
        $this->assertTrue($product->seller->is($seller));
        $this->assertTrue($product->category->is($category));
        $this->assertTrue($product->images->first()->is($image));
        $this->assertTrue($image->product->is($product));
        $this->assertTrue($image->is_primary);
    }

    public function test_user_and_product_expose_all_domain_relationships(): void
    {
        $seller = User::factory()->seller()->create();
        $reporter = User::factory()->create();
        $product = Product::factory()->for($seller, 'seller')->create();
        $boost = Boost::factory()->for($product)->for($seller)->active()->create();
        $payment = Payment::factory()->for($seller)->for($product)->paid()->create();
        $report = Report::factory()->for($reporter, 'reporter')->for($product)->create();

        $this->assertTrue($seller->products->first()->is($product));
        $this->assertTrue($seller->boosts->first()->is($boost));
        $this->assertTrue($seller->payments->first()->is($payment));
        $this->assertTrue($reporter->reports->first()->is($report));
        $this->assertTrue($product->boosts->first()->is($boost));
        $this->assertTrue($product->payments->first()->is($payment));
        $this->assertTrue($product->reports->first()->is($report));
        $this->assertTrue($boost->user->is($seller));
        $this->assertTrue($payment->user->is($seller));
        $this->assertTrue($report->reporter->is($reporter));
    }

    public function test_product_factory_states_and_soft_deletes_work(): void
    {
        $sold = Product::factory()->sold()->create();
        $hidden = Product::factory()->hidden()->create();
        $deleted = Product::factory()->deleted()->create();

        $this->assertSame(ProductStatus::Sold, $sold->status);
        $this->assertSame(ProductStatus::Hidden, $hidden->status);
        $this->assertSoftDeleted($deleted);
        $this->assertNull(Product::query()->find($deleted->id));
        $this->assertTrue(Product::withTrashed()->find($deleted->id)->is($deleted));
    }

    public function test_boost_activity_is_derived_from_its_time_window(): void
    {
        $active = Boost::factory()->active()->create();
        $scheduled = Boost::factory()->scheduled()->create();
        $expired = Boost::factory()->expired()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($scheduled->isActive());
        $this->assertFalse($expired->isActive());
    }

    public function test_payment_factory_states_use_enum_casts_and_integer_rupiah(): void
    {
        $pending = Payment::factory()->create();
        $paidBoost = Payment::factory()->forBoost()->paid()->create();
        $failed = Payment::factory()->failed()->create();
        $expired = Payment::factory()->expired()->create();
        $refunded = Payment::factory()->refunded()->create();

        $this->assertSame(PaymentStatus::Pending, $pending->status);
        $this->assertSame(PaymentType::Listing, $pending->type);
        $this->assertSame(3000, $pending->amount);
        $this->assertSame(PaymentStatus::Paid, $paidBoost->status);
        $this->assertSame(PaymentType::Boost, $paidBoost->type);
        $this->assertNotNull($paidBoost->paid_at);
        $this->assertSame(PaymentStatus::Failed, $failed->status);
        $this->assertSame(PaymentStatus::Expired, $expired->status);
        $this->assertSame(PaymentStatus::Refunded, $refunded->status);
    }

    public function test_report_factory_states_use_reason_and_status_enums(): void
    {
        $pending = Report::factory()->create(['reason' => ReportReason::Fraud]);
        $reviewed = Report::factory()->reviewed()->create();
        $resolved = Report::factory()->resolved()->create();
        $rejected = Report::factory()->rejected()->create();

        $this->assertSame(ReportReason::Fraud, $pending->reason);
        $this->assertSame(ReportStatus::Pending, $pending->status);
        $this->assertSame(ReportStatus::Reviewed, $reviewed->status);
        $this->assertSame(ReportStatus::Resolved, $resolved->status);
        $this->assertSame(ReportStatus::Rejected, $rejected->status);
    }

    public function test_setting_factory_uses_free_launch_defaults_without_boost_duration(): void
    {
        $setting = Setting::factory()->create();

        $this->assertFalse($setting->listing_fee_enabled);
        $this->assertFalse($setting->boost_fee_enabled);
        $this->assertSame(3000, $setting->listing_fee);
        $this->assertSame(3000, $setting->boost_fee);
        $this->assertNull($setting->boost_duration_hours);
    }
}
