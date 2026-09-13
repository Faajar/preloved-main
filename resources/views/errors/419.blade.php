@extends('layouts.app')

@section('title', '419 — Sesi Berakhir')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <span class="eyebrow text-stone-500">Error 419</span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">Sesi Telah Kadaluarsa</h1>
        <p class="mt-3 text-stone-600 leading-6">
            Halaman Anda telah terbuka terlalu lama dan token keamanan telah diperbarui. Silakan muat ulang halaman atau login kembali.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ url()->previous() }}" class="button-primary">Coba Lagi</a>
            <a href="{{ route('login') }}" class="button-secondary">Halaman Masuk</a>
        </div>
    </section>
@endsection
