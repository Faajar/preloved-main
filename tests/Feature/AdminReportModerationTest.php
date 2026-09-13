<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\ProductStatus;
use App\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_report_queue(): void
    {
        $report = Report::factory()->create();

        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs(User::factory()->seller()->create())->get(route('admin.reports.show', $report))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.reports.index'))->assertOk();
    }

    public function test_admin_can_filter_reports_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->create(['description' => 'Pending report']);
        Report::factory()->resolved()->create(['description' => 'Resolved report']);

        $this->actingAs($admin)->get(route('admin.reports.index', ['status' => ReportStatus::Pending->value]))
            ->assertOk()
            ->assertSee('Pending report')
            ->assertDontSee('Resolved report');
    }

    public function test_admin_can_review_then_resolve_and_hide_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $report = Report::factory()->for($product)->create();

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), [
            'status' => ReportStatus::Reviewed->value,
        ])->assertRedirect(route('admin.reports.show', $report));

        $this->assertSame(ReportStatus::Reviewed, $report->refresh()->status);
        $this->assertSame(ProductStatus::Available, $product->refresh()->status);

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), [
            'status' => ReportStatus::Resolved->value,
            'hide_product' => true,
        ])->assertRedirect(route('admin.reports.show', $report));

        $this->assertSame(ReportStatus::Resolved, $report->refresh()->status);
        $this->assertSame(ProductStatus::Hidden, $product->refresh()->status);
        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_invalid_transition_does_not_change_report_or_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $report = Report::factory()->for($product)->create();

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), [
            'status' => ReportStatus::Resolved->value,
            'hide_product' => true,
        ])->assertSessionHasErrors(['status', 'hide_product']);

        $this->assertSame(ReportStatus::Pending, $report->refresh()->status);
        $this->assertSame(ProductStatus::Available, $product->refresh()->status);
    }

    public function test_rejecting_report_does_not_hide_product_and_terminal_status_cannot_reopen(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $report = Report::factory()->for($product)->create();

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), [
            'status' => ReportStatus::Rejected->value,
        ])->assertRedirect();

        $this->assertSame(ReportStatus::Rejected, $report->refresh()->status);
        $this->assertSame(ProductStatus::Available, $product->refresh()->status);

        $this->actingAs($admin)->patch(route('admin.reports.update', $report), [
            'status' => ReportStatus::Pending->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::Rejected, $report->refresh()->status);
    }

    public function test_admin_detail_escapes_report_content_and_loads_soft_deleted_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $report = Report::factory()->for($product)->create([
            'description' => '<script>alert(1)</script>',
        ]);
        $product->delete();

        $this->actingAs($admin)->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Dihapus seller');
    }
}
