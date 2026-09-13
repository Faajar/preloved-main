@extends('layouts.app')

@section('title', '500 — Kesalahan Server')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <span class="eyebrow text-red-700">Error 500</span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">Terjadi Kesalahan Server</h1>
        <p class="mt-3 text-stone-600 leading-6">
            Maaf, sistem mengalami kendala teknis saat memproses permintaan Anda. Tim kami telah mencatat peristiwa ini.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('home') }}" class="button-primary">Kembali ke Beranda</a>
        </div>
    </section>
@endsection
