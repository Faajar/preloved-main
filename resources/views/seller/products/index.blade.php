@extends('layouts.app')

@section('title', 'Barang Saya')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Seller area</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Barang Saya</h1>
                <p class="mt-2 text-stone-600">Kelola status, foto, dan informasi barang milikmu.</p>
            </div>
            <a href="{{ route('seller.products.create') }}" class="button-primary">Titipkan Barang</a>
        </div>

        @if ($products->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Belum ada barang</h2>
                <p class="mt-2 text-stone-600">Barang yang kamu titipkan akan muncul di sini.</p>
                <a href="{{ route('seller.products.create') }}" class="button-primary mt-5">Tambah barang pertama</a>
            </div>
        @else
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
                        <div class="aspect-[4/3] bg-stone-100">
                            @if ($product->images->first())
                                <img src="{{ Storage::disk($product->images->first()->disk)->url($product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text ?? $product->name }}" class="size-full object-cover">
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-medium uppercase tracking-wide text-stone-500">{{ $product->category->name }}</p>
                                    <h2 class="mt-1 truncate text-lg font-semibold">{{ $product->name }}</h2>
                                </div>
                                <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">{{ $product->status->label() }}</span>
                            </div>
                            <p class="mt-3 text-xl font-semibold">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                            <p class="mt-2 text-sm text-stone-500">{{ $product->published_at ? 'Listing aktif' : 'Menunggu pembayaran listing' }}</p>
                            @if ($product->isBoosted())
                                <p class="mt-2 text-sm font-semibold text-emerald-700">BOOSTED sampai {{ $product->active_boost_ends_at->translatedFormat('d M Y H:i') }}</p>
                            @endif
                            <a href="{{ route('seller.products.edit', $product) }}" class="button-secondary mt-5 w-full justify-center">Kelola barang</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
