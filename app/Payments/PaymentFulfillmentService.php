<?php

namespace App\Payments;

use App\Models\Boost;
use App\Models\Payment;
use App\Models\PaymentFulfillment;
use App\Models\Product;
use App\PaymentFulfillmentStatus;
use App\PaymentStatus;
use App\PaymentType;
use App\ProductStatus;

class PaymentFulfillmentService
{
    public function fulfill(Payment $payment): void
    {
        $fulfillment = $payment->fulfillment()->lockForUpdate()->firstOrCreate([], [
            'status' => PaymentFulfillmentStatus::Pending,
        ]);

        if ($fulfillment->status === PaymentFulfillmentStatus::Fulfilled) {
            return;
        }

        $fulfillment->update([
            'status' => PaymentFulfillmentStatus::Processing,
            'attempt_count' => $fulfillment->attempt_count + 1,
            'last_failure' => null,
        ]);

        $product = Product::withTrashed()->whereKey($payment->product_id)->lockForUpdate()->first();

        if ($product === null || $product->trashed() || $product->seller_id !== $payment->user_id
            || $product->status !== ProductStatus::Available) {
            $this->fail($fulfillment, 'Listing tidak lagi memenuhi syarat untuk diaktifkan.');

            return;
        }

        if ($payment->type === PaymentType::Listing) {
            if (! $product->category()->where('is_active', true)->exists()) {
                $this->fail($fulfillment, 'Kategori listing tidak aktif.');

                return;
            }

            $product->update(['published_at' => $product->published_at ?? now()]);
            $fulfillment->update([
                'status' => PaymentFulfillmentStatus::Fulfilled,
                'fulfilled_at' => now(),
                'last_failure' => null,
                'next_retry_at' => null,
            ]);

            return;
        }

        if (! is_int($fulfillment->boost_duration_hours) || $fulfillment->boost_duration_hours <= 0
            || ! Product::query()->publiclyVisible()->whereKey($product->id)->exists()) {
            $this->fail($fulfillment, 'Listing tidak memenuhi syarat untuk menerima boost.');

            return;
        }

        $product->seller()->lockForUpdate()->firstOrFail();
        $hasOtherActiveBoost = Boost::query()
            ->active()
            ->where('user_id', $payment->user_id)
            ->where('product_id', '!=', $product->id)
            ->whereHas('product', fn ($query) => $query->publiclyVisible())
            ->exists();

        if ($hasOtherActiveBoost) {
            $this->fail($fulfillment, 'Seller sudah memiliki boost aktif untuk listing lain.');

            return;
        }

        $payment->boost()->firstOrCreate([], [
            'product_id' => $product->id,
            'user_id' => $payment->user_id,
            'starts_at' => now(),
            'ends_at' => now()->addHours($fulfillment->boost_duration_hours),
        ]);
        $fulfillment->update([
            'status' => PaymentFulfillmentStatus::Fulfilled,
            'fulfilled_at' => now(),
            'last_failure' => null,
            'next_retry_at' => null,
        ]);
    }

    public function reverseRefund(Payment $payment): void
    {
        $fulfillment = $payment->fulfillment()->lockForUpdate()->first();

        if ($fulfillment?->status !== PaymentFulfillmentStatus::Fulfilled) {
            return;
        }

        if ($payment->type === PaymentType::Listing) {
            $hasOtherEntitlement = Payment::query()
                ->where('product_id', $payment->product_id)
                ->where('type', PaymentType::Listing)
                ->where('status', PaymentStatus::Paid)
                ->whereHas('fulfillment', fn ($query) => $query->where('status', PaymentFulfillmentStatus::Fulfilled))
                ->whereKeyNot($payment->id)
                ->exists();

            if (! $hasOtherEntitlement) {
                Product::withTrashed()->whereKey($payment->product_id)->update(['published_at' => null]);
            }
        } else {
            $payment->boost()->where('ends_at', '>', now())->update(['ends_at' => now()]);
        }

        $fulfillment->update([
            'status' => PaymentFulfillmentStatus::Reversed,
            'reversed_at' => now(),
        ]);
    }

    private function fail(PaymentFulfillment $fulfillment, string $failure): void
    {
        $fulfillment->update([
            'status' => PaymentFulfillmentStatus::Failed,
            'last_failure' => $failure,
        ]);
    }
}
