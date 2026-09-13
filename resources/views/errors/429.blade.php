@extends('layouts.app')

@section('title', '429 — Terlalu Banyak Permintaan')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <span class="eyebrow text-amber-700">Error 429</span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">Terlalu Banyak Permintaan</h1>
        <p class="mt-3 text-stone-600 leading-6">
            Sistem mendeteksi aktivitas yang terlalu cepat dari perangkat Anda. Silakan tunggu beberapa saat sebelum mencoba kembali.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('home') }}" class="button-primary">Kembali ke Beranda</a>
        </div>
    </section>
@endsection
