@extends('layouts.app')

@section('title', 'Detail Transaksi #'.$payment->id)

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke daftar pembayaran</a>

        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Audit Pembayaran</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                    {{ $payment->currentAttempt?->invoice_number ?? 'Pembayaran #'.$payment->id }}
                </h1>
                <p class="mt-2 text-stone-600">
                    Jenis: <strong>{{ $payment->type->label() }}</strong> · 
                    Status: <strong class="text-stone-900">{{ $payment->status->label() }}</strong>
                </p>
            </div>
            <time class="text-sm text-stone-500" datetime="{{ $payment->created_at->toAtomString() }}">{{ $payment->created_at->translatedFormat('d M Y H:i') }}</time>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Informasi Transaksi</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-stone-500">Nominal Tagihan</dt>
                            <dd class="text-2xl font-bold text-stone-900">Rp{{ number_format($payment->amount, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Mata Uang</dt>
                            <dd class="font-medium text-stone-800">{{ $payment->currency }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Waktu Dibayar</dt>
                            <dd class="font-medium text-stone-800">{{ $payment->paid_at ? $payment->paid_at->translatedFormat('d M Y H:i:s') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Waktu Kadaluarsa</dt>
                            <dd class="font-medium text-stone-800">{{ $payment->expired_at ? $payment->expired_at->translatedFormat('d M Y H:i:s') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Waktu Refund</dt>
                            <dd class="font-medium text-stone-800">{{ $payment->refunded_at ? $payment->refunded_at->translatedFormat('d M Y H:i:s') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Durasi Boost</dt>
                            <dd class="font-medium text-stone-800">{{ $payment->boost_duration_hours ? $payment->boost_duration_hours.' jam' : '—' }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Status Fulfillment Hak (Entitlement)</h2>
                    @if ($payment->fulfillment)
                        <div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm">
                            <p>Status: <span class="font-semibold text-stone-900">{{ strtoupper($payment->fulfillment->status->value ?? $payment->fulfillment->status) }}</span></p>
                            @if ($payment->fulfillment->fulfilled_at)
                                <p class="mt-1 text-xs text-stone-600">Dipenuhi pada: {{ $payment->fulfillment->fulfilled_at->translatedFormat('d M Y H:i:s') }}</p>
                            @endif
                            @if ($payment->fulfillment->reversed_at)
                                <p class="mt-1 text-xs text-red-600">Dibatalkan/Reversed pada: {{ $payment->fulfillment->reversed_at->translatedFormat('d M Y H:i:s') }}</p>
                            @endif
                            @if ($payment->fulfillment->last_failure)
                                <p class="mt-1 text-xs text-red-700">Error terakhir: {{ $payment->fulfillment->last_failure }}</p>
                            @endif
                        </div>
                    @else
                        <p class="mt-4 text-sm text-stone-500">Belum ada record fulfillment untuk pembayaran ini.</p>
                    @endif
                </article>

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Riwayat Percobaan Checkout ({{ $payment->attempts->count() }})</h2>
                    @if ($payment->attempts->isEmpty())
                        <p class="mt-3 text-sm text-stone-500">Belum ada percobaan checkout yang dibuat.</p>
                    @else
                        <div class="mt-4 divide-y divide-stone-200 overflow-x-auto text-sm">
                            @foreach ($payment->attempts as $att)
                                <div class="py-3">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-stone-900">Attempt #{{ $att->sequence }} · {{ $att->invoice_number }}</span>
                                        <span class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-700">{{ $att->status }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-stone-500">Provider: {{ $att->provider ?? 'DOKU' }} · Ref: {{ $att->provider_reference ?? '—' }}</p>
                                    @if ($att->expires_at)
                                        <p class="text-xs text-stone-500">Batas bayar: {{ $att->expires_at->translatedFormat('d M Y H:i') }}</p>
                                    @endif
                                    @if ($att->last_provider_error)
                                        <p class="mt-1 text-xs text-red-600">Error: {{ $att->last_provider_error }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>

            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Pengguna / Seller</h2>
                    <dl class="mt-4 grid gap-2 text-sm">
                        <div><dt class="text-stone-500">Nama</dt><dd class="font-medium text-stone-900">{{ $payment->user->name }}</dd></div>
                        <div><dt class="text-stone-500">Email</dt><dd class="font-medium text-stone-900">{{ $payment->user->email }}</dd></div>
                        <div><dt class="text-stone-500">Role</dt><dd class="font-medium text-stone-900">{{ $payment->user->role->value }}</dd></div>
                    </dl>
                </article>

                @if ($payment->product)
                    <article class="rounded-2xl border border-stone-200 bg-white p-6">
                        <h2 class="text-lg font-semibold">Barang Terkait</h2>
                        <dl class="mt-4 grid gap-2 text-sm">
                            <div><dt class="text-stone-500">Nama Barang</dt><dd class="font-medium text-stone-900">{{ $payment->product->name }}</dd></div>
                            <div><dt class="text-stone-500">Status Produk</dt><dd class="font-medium text-stone-900">{{ $payment->product->status->label() }}</dd></div>
                        </dl>
                        <a href="{{ route('admin.products.show', $payment->product->id) }}" class="button-secondary mt-4 text-xs">Buka moderasi barang →</a>
                    </article>
                @endif

                @if ($payment->refunds->isNotEmpty())
                    <article class="rounded-2xl border border-purple-200 bg-purple-50 p-6">
                        <h2 class="text-lg font-semibold text-purple-950">Catatan Refund ({{ $payment->refunds->count() }})</h2>
                        <div class="mt-3 divide-y divide-purple-200">
                            @foreach ($payment->refunds as $ref)
                                <div class="py-2.5 text-xs text-purple-900">
                                    <p class="font-semibold">Rp{{ number_format($ref->amount, 0, ',', '.') }} ({{ $ref->kind }})</p>
                                    <p class="mt-0.5">Ref Provider: {{ $ref->provider_refund_reference ?? '—' }}</p>
                                    @if ($ref->requires_review)
                                        <span class="inline-block mt-1 rounded bg-amber-200 px-1.5 py-0.5 font-medium text-amber-900">Perlu Review Admin</span>
                                    @endif
                                    @if ($ref->reason)
                                        <p class="mt-1 text-purple-800">Alasan: {{ $ref->reason }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endif

                @if ($payment->webhookEvents->isNotEmpty())
                    <article class="rounded-2xl border border-stone-200 bg-white p-6">
                        <h2 class="text-lg font-semibold">Event Webhook DOKU ({{ $payment->webhookEvents->count() }})</h2>
                        <div class="mt-3 divide-y divide-stone-100 text-xs">
                            @foreach ($payment->webhookEvents as $ev)
                                <div class="py-2.5">
                                    <p class="font-mono font-medium text-stone-900">{{ $ev->provider_event_reference ?? 'Event #'.$ev->id }}</p>
                                    <p class="text-stone-500">Status Provider: {{ $ev->provider_status }} · Status Proses: {{ $ev->processing_status }}</p>
                                    @if ($ev->processing_error)
                                        <p class="text-red-600 mt-1">{{ $ev->processing_error }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endif
            </div>
        </div>
    </section>
@endsection
