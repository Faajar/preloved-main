@extends('layouts.app')

@section('title', '403 — Akses Ditolak')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <span class="eyebrow text-amber-700">Error 403</span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">Akses Ditolak</h1>
        <p class="mt-3 text-stone-600 leading-6">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman atau fitur ini.' }}
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('home') }}" class="button-primary">Kembali ke Beranda</a>
            <a href="{{ route('explore') }}" class="button-secondary">Jelajahi Preloved</a>
        </div>
    </section>
@endsection
