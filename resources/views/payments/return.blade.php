@extends('layouts.app')

@section('title', 'Status Pembayaran')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <span class="eyebrow">DOKU Checkout</span>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Status pembayaran: {{ $payment->status->label() }}</h1>
        <p class="mt-4 text-stone-600">Kami menunggu dan memverifikasi notifikasi pembayaran langsung dari DOKU. Halaman ini tidak mengaktifkan listing atau boost secara otomatis.</p>
        <a href="{{ route('seller.payments.show', $payment) }}" class="button-primary mt-8">Lihat detail pembayaran</a>
    </section>
@endsection
