@extends('layouts.app')

@section('title', 'Explore Barang Preloved')
@section('meta_description', 'Cari dan filter listing barang preloved berdasarkan kategori, harga, kondisi, dan lokasi.')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <span class="eyebrow">Explore</span>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Temukan barang yang tepat</h1>
        <p class="mt-3 text-stone-600">Gunakan pencarian dan filter untuk mempersempit hasil.</p>

        <form method="GET" action="{{ route('explore') }}" class="mt-8 grid gap-4 rounded-2xl border border-stone-200 bg-white p-5 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="q" class="form-label">Kata kunci</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Nama atau deskripsi barang" class="input-field">
            </div>
            <div>
                <label for="category" class="form-label">Kategori</label>
                <select id="category" name="category" class="input-field">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="condition" class="form-label">Kondisi</label>
                <select id="condition" name="condition" class="input-field">
                    <option value="">Semua kondisi</option>
                    @foreach (App\ProductCondition::cases() as $condition)
                        <option value="{{ $condition->value }}" @selected(request('condition') === $condition->value)>{{ $condition->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="min_price" class="form-label">Harga minimum</label>
                <input id="min_price" name="min_price" type="number" min="0" value="{{ request('min_price') }}" class="input-field">
            </div>
            <div>
                <label for="max_price" class="form-label">Harga maksimum</label>
                <input id="max_price" name="max_price" type="number" min="0" value="{{ request('max_price') }}" class="input-field">
                @error('max_price')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="location" class="form-label">Lokasi</label>
                <input id="location" name="location" type="text" value="{{ request('location') }}" placeholder="Contoh: Bandung" class="input-field">
            </div>
            <div>
                <label for="sort" class="form-label">Urutkan</label>
                <select id="sort" name="sort" class="input-field">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Terbaru</option>
                    <option value="price_low" @selected(request('sort') === 'price_low')>Harga termurah</option>
                    <option value="price_high" @selected(request('sort') === 'price_high')>Harga termahal</option>
                </select>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row lg:col-span-4 lg:justify-end">
                <a href="{{ route('explore') }}" class="button-secondary justify-center">Reset</a>
                <button type="submit" class="button-primary justify-center">Terapkan filter</button>
            </div>
        </form>

        <div class="mt-8 flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">{{ $products->total() }} barang ditemukan</h2>
        </div>

        @if ($products->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h3 class="text-xl font-semibold">Tidak ada barang yang cocok</h3>
                <p class="mt-2 text-stone-600">Coba ubah kata kunci atau kurangi filter pencarian.</p>
                <a href="{{ route('explore') }}" class="button-secondary mt-5">Hapus filter</a>
            </div>
        @else
            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :$product />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
