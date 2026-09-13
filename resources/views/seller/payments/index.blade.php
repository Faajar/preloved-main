@extends('layouts.app')

@section('title', 'Riwayat Pembayaran')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <span class="eyebrow">Seller</span>
        <div class="mt-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Riwayat pembayaran</h1>
                <p class="mt-2 text-stone-600">Pembayaran ini hanya untuk biaya listing dan boost, bukan pembelian barang.</p>
            </div>
            <a href="{{ route('seller.dashboard') }}" class="button-secondary">Kembali ke dashboard</a>
        </div>

        <div class="mt-8 grid gap-4">
            @forelse ($payments as $payment)
                <a href="{{ route('seller.payments.show', $payment) }}" class="rounded-2xl border border-stone-200 bg-white p-5 hover:border-emerald-300">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div>
                            <p class="font-semibold">{{ $payment->type->label() }} · {{ $payment->product?->name ?? 'Listing dihapus' }}</p>
                            <p class="mt-1 text-sm text-stone-500">{{ $payment->currentAttempt?->invoice_number ?? 'Belum dibuat' }} · {{ $payment->created_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                        <div class="sm:text-right">
                            <p class="font-semibold">Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                            <p class="mt-1 text-sm text-stone-600">{{ $payment->status->label() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-300 p-8 text-center text-stone-600">Belum ada riwayat pembayaran.</div>
            @endforelse
        </div>

        <div class="mt-8">{{ $payments->links() }}</div>
    </section>
@endsection
