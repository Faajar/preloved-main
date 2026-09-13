@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', 'Temukan barang preloved berkualitas dan titipkan barangmu di Preloved IN.')

@section('content')
    <section class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
        <div class="max-w-2xl">
            <span class="eyebrow">Preloved yang lebih berarti</span>
            <h1 class="mt-5 text-4xl font-semibold tracking-tight text-stone-950 sm:text-6xl">Temukan Barang Preloved Favoritmu</h1>
            <p class="mt-5 max-w-xl text-lg leading-8 text-stone-600">Barang Lama, Kesempatan Baru. Temukan barang berkualitas atau titipkan barang yang siap menemukan pemilik barunya.</p>

            <form action="{{ route('explore') }}" class="mt-8 flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm sm:flex-row" role="search">
                <label for="q" class="sr-only">Cari barang preloved</label>
                <input id="q" name="q" type="search" placeholder="Cari barang preloved..." class="input-field mt-0 border-0 sm:flex-1">
                <button type="submit" class="button-primary justify-center">Cari</button>
            </form>
        </div>

        <div class="rounded-3xl bg-emerald-800 p-8 text-white sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-200">Launch period</p>
            <h2 class="mt-4 text-3xl font-semibold">Titipkan barangmu secara gratis.</h2>
            <p class="mt-4 leading-7 text-emerald-50">Buat akun sebagai buyer, lalu aktifkan seller saat kamu siap menitipkan barang.</p>
            <div class="mt-8">
                @guest
                    <a href="{{ route('register') }}" class="button-light">Mulai sekarang</a>
                @elseif (auth()->user()->canSell())
                    <a href="{{ route('seller.products.create') }}" class="button-light">Titipkan Barang</a>
                @else
                    <a href="{{ route('dashboard') }}" class="button-light">Aktifkan seller</a>
                @endguest
            </div>
        </div>
    </section>

    <section id="categories" class="border-y border-stone-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <span class="eyebrow">Kategori</span>
            <div class="mt-3 flex items-end justify-between gap-4">
                <h2 class="text-2xl font-semibold tracking-tight">Jelajahi berdasarkan kategori</h2>
                <a href="{{ route('categories.index') }}" class="hidden text-sm font-semibold text-emerald-700 sm:block">Lihat semua</a>
            </div>
            <div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="rounded-xl border border-stone-200 px-4 py-5 font-semibold transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <span class="eyebrow">Terbaru</span>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight">Listing terbaru</h2>
            </div>
            <a href="{{ route('explore') }}" class="text-sm font-semibold text-emerald-700">Explore</a>
        </div>

        @if ($latestProducts->isEmpty())
            <div class="mt-7 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center">
                <h3 class="text-xl font-semibold">Belum ada listing aktif</h3>
                <p class="mt-2 text-stone-600">Barang terbaru akan tampil di sini setelah seller mulai menitipkan barang.</p>
            </div>
        @else
            <div class="mt-7 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestProducts as $product)
                    <x-product-card :$product />
                @endforeach
            </div>
        @endif
    </section>
@endsection
