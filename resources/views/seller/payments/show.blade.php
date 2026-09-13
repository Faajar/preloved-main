@extends('layouts.app')

@section('title', 'Detail Pembayaran')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('seller.payments.index') }}" class="text-sm font-semibold text-emerald-700">← Riwayat pembayaran</a>

        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 sm:p-8">
            <span class="eyebrow">{{ $payment->type->label() }}</span>
            <h1 class="mt-3 text-2xl font-semibold">{{ $payment->product?->name ?? 'Listing dihapus' }}</h1>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-stone-500">Status</dt><dd class="font-semibold">{{ $payment->status->label() }}</dd></div>
                <div><dt class="text-sm text-stone-500">Nominal</dt><dd class="font-semibold">Rp{{ number_format($payment->amount, 0, ',', '.') }}</dd></div>
                <div><dt class="text-sm text-stone-500">Invoice</dt><dd class="break-all font-medium">{{ $payment->currentAttempt?->invoice_number ?? 'Belum dibuat' }}</dd></div>
                <div><dt class="text-sm text-stone-500">Provider</dt><dd class="font-medium">{{ strtoupper($payment->currentAttempt?->provider ?? 'Belum dipilih') }}</dd></div>
            </dl>

            @if ($payment->fulfillment?->last_failure)
                <p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Pembayaran diterima, tetapi aktivasi perlu ditinjau admin: {{ $payment->fulfillment->last_failure }}</p>
            @endif

            @if ($payment->hasUsableCheckout())
                <a href="{{ $payment->currentAttempt->checkout_url }}" rel="noopener noreferrer" class="button-primary mt-6">Lanjutkan pembayaran di DOKU</a>
            @elseif (in_array($payment->status, [App\PaymentStatus::Pending, App\PaymentStatus::Expired, App\PaymentStatus::Failed], true))
                <form method="POST" action="{{ route('seller.payments.checkout', $payment) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="button-primary">
                        {{ $payment->status === App\PaymentStatus::Pending ? 'Buat checkout DOKU' : 'Buat percobaan pembayaran baru' }}
                    </button>
                </form>
            @endif
            @error('payment')<p class="form-error mt-3">{{ $message }}</p>@enderror

            <p class="mt-6 text-sm text-stone-500">Status tidak ditentukan oleh halaman kembali browser. Aktivasi dilakukan setelah notifikasi server DOKU terverifikasi.</p>
        </div>
    </section>
@endsection
