<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\PaymentAttemptStatus;
use App\PaymentFulfillmentStatus;
use App\Payments\PaymentFulfillmentService;
use App\Payments\PaymentReconciler;
use App\PaymentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile {--limit=50 : Jumlah maksimum transaksi yang diproses}';

    protected $description = 'Rekonsiliasi status pembayaran tertunda, proses fulfillment yang belum selesai, dan perbarui attempt kadaluarsa';

    public function handle(
        PaymentReconciler $reconciler,
        PaymentFulfillmentService $fulfillmentService,
    ): int {
        $limit = (int) $this->option('limit');
        $reconciledCount = 0;
        $fulfilledCount = 0;

        // 1. Rekonsiliasi attempt yang pending atau ambigu atau kadaluarsa
        $attempts = PaymentAttempt::query()
            ->whereIn('status', [PaymentAttemptStatus::CheckoutCreated, PaymentAttemptStatus::Ambiguous])
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Pending))
            ->where(function ($query): void {
                $query->where('expires_at', '<=', now())
                    ->orWhere('created_at', '<=', now()->subMinutes(10));
            })
            ->latest('id')
            ->limit($limit)
            ->get();

        foreach ($attempts as $attempt) {
            try {
                $reconciler->reconcile($attempt);
                $reconciledCount++;
            } catch (Throwable $exception) {
                Log::warning('Gagal merekonsiliasi payment attempt #'.$attempt->id.': '.$exception->getMessage());
                $this->warn('Gagal rekonsiliasi attempt #'.$attempt->id.': '.$exception->getMessage());
            }
        }

        // 2. Coba penuhi kembali payment yang sudah Paid tapi belum Fulfilled
        $unfulfilledPayments = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereHas('fulfillment', fn ($query) => $query->where('status', '!=', PaymentFulfillmentStatus::Fulfilled))
            ->latest('id')
            ->limit($limit)
            ->get();

        foreach ($unfulfilledPayments as $payment) {
            try {
                $fulfillmentService->fulfill($payment);
                $fulfilledCount++;
            } catch (Throwable $exception) {
                Log::error('Gagal memenuhi payment #'.$payment->id.': '.$exception->getMessage());
                $this->warn('Gagal fulfillment payment #'.$payment->id.': '.$exception->getMessage());
            }
        }

        $this->info("Rekonsiliasi selesai: {$reconciledCount} attempt direkonsiliasi, {$fulfilledCount} fulfillment diproses ulang.");

        return self::SUCCESS;
    }
}
