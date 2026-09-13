<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Boost;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\PaymentStatus;
use App\PaymentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProductBoostController extends Controller
{
    public function __invoke(Product $product): RedirectResponse
    {
        Gate::authorize('boost', $product);

        $settings = Setting::query()->findOrFail(1);

        if (! is_int($settings->boost_duration_hours) || $settings->boost_duration_hours <= 0) {
            return back()->withErrors(['boost' => 'Durasi boost belum dikonfigurasi. Boost belum dapat diaktifkan.']);
        }

        if (! Product::query()->publiclyVisible()->whereKey($product->id)->exists()) {
            return back()->withErrors(['boost' => 'Hanya listing aktif yang dapat di-boost.']);
        }

        $result = DB::transaction(function () use ($product, $settings): string|Payment {
            $product->seller()->lockForUpdate()->firstOrFail();

            $activeBoost = Boost::query()
                ->active()
                ->where('user_id', $product->seller_id)
                ->whereHas('product', fn ($query) => $query->publiclyVisible())
                ->first();

            if ($activeBoost?->product_id === $product->id) {
                return 'already_active';
            }

            if ($activeBoost !== null) {
                return 'seller_limit';
            }

            if ($settings->boost_fee_enabled) {
                $pendingPayment = Payment::query()
                    ->where('user_id', $product->seller_id)
                    ->where('type', PaymentType::Boost)
                    ->where('status', PaymentStatus::Pending)
                    ->where(function ($query): void {
                        $query->whereDoesntHave('attempts')
                            ->orWhereHas('currentAttempt', fn ($attemptQuery) => $attemptQuery
                                ->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now()));
                    })
                    ->latest()
                    ->first();

                if ($pendingPayment !== null) {
                    return $pendingPayment->product_id === $product->id ? $pendingPayment : 'seller_limit';
                }

                $payment = $product->payments()->create([
                    'user_id' => $product->seller_id,
                    'type' => PaymentType::Boost,
                    'amount' => $settings->boost_fee,
                    'currency' => 'IDR',
                    'status' => PaymentStatus::Pending,
                ]);
                $payment->fulfillment()->create([
                    'status' => 'pending',
                    'boost_duration_hours' => $settings->boost_duration_hours,
                ]);

                return $payment;
            }

            $product->boosts()->create([
                'user_id' => $product->seller_id,
                'starts_at' => now(),
                'ends_at' => now()->addHours($settings->boost_duration_hours),
            ]);

            return 'created';
        });

        if ($result instanceof Payment) {
            return redirect()->route('seller.payments.show', $result)
                ->with('status', 'Pembayaran boost dibuat. Lanjutkan melalui DOKU Checkout.');
        }

        return match ($result) {
            'already_active' => back()->with('status', 'Boost untuk barang ini masih aktif.'),
            'seller_limit' => back()->withErrors(['boost' => 'Kamu sudah memiliki boost aktif atau pembayaran boost yang masih menunggu.']),
            default => back()->with('status', 'Boost gratis berhasil diaktifkan.'),
        };
    }
}
