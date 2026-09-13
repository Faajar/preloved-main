<?php

use App\Http\Controllers\Admin\CategoryActivationController as AdminCategoryActivationController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DokuPaymentNotificationController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seller\BecomeSellerController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\PaymentCheckoutController;
use App\Http\Controllers\Seller\PaymentController as SellerPaymentController;
use App\Http\Controllers\Seller\PrimaryProductImageController;
use App\Http\Controllers\Seller\ProductBoostController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\ProductImageController;
use App\Http\Controllers\Seller\SoldProductController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/explore', ExploreController::class)->name('explore');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/sellers/{seller}', [SellerController::class, 'show'])->name('sellers.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::post('/payments/doku/notifications', DokuPaymentNotificationController::class)->name('payments.doku.notifications');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:registration');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('password.change');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/become-seller', BecomeSellerController::class)
        ->middleware('not.suspended')
        ->name('seller.activate');
    Route::post('/products/{product:slug}/reports', [ProductReportController::class, 'store'])
        ->middleware(['throttle:reports', 'not.suspended'])
        ->name('products.reports.store');
});

Route::middleware(['auth', 'seller', 'not.suspended'])->prefix('seller')->name('seller.')->group(function (): void {
    Route::get('/dashboard', SellerDashboardController::class)->name('dashboard');
    Route::get('/payments', [SellerPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [SellerPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/checkout', PaymentCheckoutController::class)->name('payments.checkout');
    Route::get('/payments/{payment}/return', PaymentReturnController::class)->name('payments.return');
    Route::resource('products', SellerProductController::class)->except('show');
    Route::post('/products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('/products/{product}/images/{productImage}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::patch('/products/{product}/images/{productImage}/primary', PrimaryProductImageController::class)->name('products.images.primary');
    Route::patch('/products/{product}/sold', SoldProductController::class)->name('products.sold');
    Route::post('/products/{product}/boost', ProductBoostController::class)->name('products.boosts.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/warn', [AdminUserController::class, 'warn'])->name('users.warn');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend');
    Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
    Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::patch('/categories/{category}/activation', AdminCategoryActivationController::class)->name('categories.activation.update');
    Route::resource('categories', AdminCategoryController::class)->except('show');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
});
