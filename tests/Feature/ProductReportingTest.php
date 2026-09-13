<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\ReportReason;
use App\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_report_a_public_product(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($buyer)->post(route('products.reports.store', $product), [
            'reason' => ReportReason::Fraud->value,
            'description' => 'Informasi seller mencurigakan.',
            'reporter_id' => $product->seller_id,
            'product_id' => Product::factory()->create()->id,
            'status' => ReportStatus::Resolved->value,
        ]);

        $report = Report::query()->sole();
        $response->assertRedirect();
        $this->assertSame($buyer->id, $report->reporter_id);
        $this->assertSame($product->id, $report->product_id);
        $this->assertSame(ReportReason::Fraud, $report->reason);
        $this->assertSame(ReportStatus::Pending, $report->status);
    }

    public function test_seller_can_report_another_sellers_product_but_not_own_product(): void
    {
        $seller = User::factory()->seller()->create();
        $otherProduct = Product::factory()->create();
        $ownProduct = Product::factory()->for($seller, 'seller')->create();

        $this->actingAs($seller)->post(route('products.reports.store', $otherProduct), [
            'reason' => ReportReason::Spam->value,
        ])->assertRedirect();

        $this->actingAs($seller)->post(route('products.reports.store', $ownProduct), [
            'reason' => ReportReason::Spam->value,
        ])->assertForbidden();

        $this->assertSame(1, Report::query()->count());
    }

    public function test_guest_must_log_in_and_admin_cannot_submit_public_report(): void
    {
        $product = Product::factory()->create();

        $this->post(route('products.reports.store', $product), [
            'reason' => ReportReason::Spam->value,
        ])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->admin()->create())->post(route('products.reports.store', $product), [
            'reason' => ReportReason::Spam->value,
        ])->assertForbidden();

        $this->assertSame(0, Report::query()->count());
    }

    public function test_other_reason_requires_non_empty_description(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($buyer)->post(route('products.reports.store', $product), [
            'reason' => ReportReason::Other->value,
            'description' => '   ',
        ])->assertSessionHasErrors('description');

        $this->assertSame(0, Report::query()->count());
    }

    public function test_same_user_cannot_report_same_product_twice(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create();
        Report::factory()->for($buyer, 'reporter')->for($product)->create();

        $this->actingAs($buyer)->post(route('products.reports.store', $product), [
            'reason' => ReportReason::FalseInformation->value,
        ])->assertSessionHasErrors('report');

        $this->assertSame(1, Report::query()->count());
    }

    public function test_different_users_can_report_the_same_product(): void
    {
        $product = Product::factory()->create();

        foreach (User::factory()->count(2)->create() as $buyer) {
            $this->actingAs($buyer)->post(route('products.reports.store', $product), [
                'reason' => ReportReason::Spam->value,
            ])->assertRedirect();
        }

        $this->assertSame(2, Report::query()->count());
    }
}
