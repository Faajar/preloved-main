<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\PaymentStatus;
use App\ProductStatus;
use App\ReportStatus;
use App\UserRole;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userMetrics = [
            'total' => User::query()->count(),
            'buyers' => User::query()->where('role', UserRole::Buyer)->count(),
            'sellers' => User::query()->where('role', UserRole::Seller)->count(),
            'admins' => User::query()->where('role', UserRole::Admin)->count(),
        ];

        $productMetrics = [
            'total' => Product::query()->count(),
            'available' => Product::query()->where('status', ProductStatus::Available)->whereNotNull('published_at')->count(),
            'sold' => Product::query()->where('status', ProductStatus::Sold)->count(),
            'hidden' => Product::query()->where('status', ProductStatus::Hidden)->count(),
        ];

        $reportMetrics = [
            'pending' => Report::query()->where('status', ReportStatus::Pending)->count(),
            'reviewed' => Report::query()->where('status', ReportStatus::Reviewed)->count(),
            'resolved' => Report::query()->where('status', ReportStatus::Resolved)->count(),
            'total' => Report::query()->count(),
        ];

        $paymentMetrics = [
            'paid_count' => Payment::query()->where('status', PaymentStatus::Paid)->count(),
            'pending_count' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
            'refunded_count' => Payment::query()->where('status', PaymentStatus::Refunded)->count(),
            'total_revenue' => (int) Payment::query()->where('status', PaymentStatus::Paid)->sum('amount'),
        ];

        $categoriesCount = Category::query()->count();
        $settings = Setting::query()->find(1);

        return view('admin.dashboard', compact(
            'userMetrics',
            'productMetrics',
            'reportMetrics',
            'paymentMetrics',
            'categoriesCount',
            'settings'
        ));
    }
}
