@extends('layouts.app')

@section('title', 'Monitoring Pembayaran')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Admin keuangan</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Monitoring Pembayaran Fee</h1>
                <p class="mt-2 text-stone-600">Pantau transaksi biaya listing dan biaya boost yang masuk melalui gateway pembayaran.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="button-secondary">Dashboard Admin</a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Total Pendapatan Sukses</p>
                <p class="mt-2 text-2xl font-bold text-emerald-800">Rp{{ number_format($metrics['total_revenue'], 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Transaksi Paid</p>
                <p class="mt-2 text-2xl font-bold text-stone-900">{{ number_format($metrics['paid_count']) }}</p>
            </article>
            <article class="rounded-2xl border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Transaksi Pending</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($metrics['pending_count']) }}</p>
            </article>
            <article class="rounded-2xl border border-stone-200 bg-white p-5">
                <p class="text-sm text-stone-500">Transaksi Refunded</p>
                <p class="mt-2 text-2xl font-bold text-stone-600">{{ number_format($metrics['refunded_count']) }}</p>
            </article>
        </div>

        <form method="GET" action="{{ route('admin.payments.index') }}" class="mt-8 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 sm:grid-cols-4 sm:items-end">
            <div>
                <label for="q" class="form-label">Cari invoice / user / barang</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" placeholder="Ketik kata kunci..." class="input-field">
            </div>

            <div>
                <label for="status" class="form-label">Status pembayaran</label>
                <select id="status" name="status" class="input-field">
                    <option value="">Semua status</option>
                    @foreach (App\PaymentStatus::cases() as $st)
                        <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="type" class="form-label">Jenis fee</label>
                <select id="type" name="type" class="input-field">
                    <option value="">Semua jenis</option>
                    @foreach (App\PaymentType::cases() as $tp)
                        <option value="{{ $tp->value }}" @selected(request('type') === $tp->value)>{{ $tp->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="button-primary flex-1 justify-center">Terapkan</button>
                <a href="{{ route('admin.payments.index') }}" class="button-secondary justify-center">Reset</a>
            </div>
        </form>

        @if ($payments->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Belum ada transaksi pembayaran</h2>
                <p class="mt-2 text-stone-600">Transaksi fee listing dan boost akan muncul di sini.</p>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="divide-y divide-stone-200">
                    @foreach ($payments as $payment)
                        <a href="{{ route('admin.payments.show', $payment) }}" class="grid gap-4 p-5 transition hover:bg-stone-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-stone-900">
                                        {{ $payment->currentAttempt?->invoice_number ?? '#'.$payment->id }}
                                    </span>
                                    <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-700">{{ $payment->type->label() }}</span>
                                    @if ($payment->status === App\PaymentStatus::Paid)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Paid</span>
                                    @elseif ($payment->status === App\PaymentStatus::Pending)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Pending</span>
                                    @elseif ($payment->status === App\PaymentStatus::Refunded)
                                        <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">Refunded</span>
                                    @else
                                        <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">{{ $payment->status->label() }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-stone-600">
                                    Seller: <span class="font-medium text-stone-800">{{ $payment->user->name }}</span> ({{ $payment->user->email }})
                                    @if ($payment->product)
                                        · Barang: <span class="font-medium text-stone-800">{{ $payment->product->name }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-base font-bold text-stone-950">Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
                                <time class="text-xs text-stone-500" datetime="{{ $payment->created_at->toAtomString() }}">{{ $payment->created_at->translatedFormat('d M Y H:i') }}</time>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="mt-8">{{ $payments->links() }}</div>
        @endif
    </section>
@endsection
