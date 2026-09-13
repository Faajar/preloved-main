@extends('layouts.app')

@section('title', 'Barang dari '.$seller->name)
@section('meta_description', 'Lihat barang preloved aktif dari '.$seller->name.' di Preloved IN.')
@section('canonical', route('sellers.show', $seller))

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 sm:p-8">
            <div class="flex items-center gap-5">
                <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-2xl font-semibold text-emerald-800">
                    {{ Str::upper(Str::substr($seller->name, 0, 1)) }}
                </div>
                <div>
                    <span class="eyebrow">Profil seller</span>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $seller->name }}</h1>
                    <p class="mt-2 text-stone-600">{{ $activeListingCount }} listing aktif</p>
                </div>
            </div>
        </div>

        <div class="mt-10 flex items-end justify-between gap-4">
            <div>
                <span class="eyebrow">Barang seller</span>
                <h2 class="mt-3 text-2xl font-semibold">Listing aktif</h2>
            </div>
            <a href="{{ route('explore') }}" class="text-sm font-semibold text-emerald-700">Explore semua</a>
        </div>

        @if ($products->isEmpty())
            <div class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h3 class="text-xl font-semibold">Seller belum memiliki listing aktif</h3>
                <p class="mt-2 text-stone-600">Listing yang tersedia akan tampil di sini.</p>
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :$product />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
