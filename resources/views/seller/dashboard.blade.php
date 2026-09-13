@extends('layouts.app')

@section('title', 'Dashboard Seller')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <span class="eyebrow">Seller area</span>
        <div class="mt-3 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Dashboard Seller</h1>
                <p class="mt-3 text-stone-600">Kelola barang yang kamu titipkan di Preloved IN.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('seller.products.index') }}" class="button-secondary">Barang Saya</a>
                <a href="{{ route('seller.products.create') }}" class="button-primary">Titipkan Barang</a>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Listing aktif', 'value' => $summary['active']],
                ['label' => 'Menunggu pembayaran', 'value' => $summary['pending']],
                ['label' => 'Terjual', 'value' => $summary['sold']],
                ['label' => 'Disembunyikan', 'value' => $summary['hidden']],
                ['label' => 'Sedang boosted', 'value' => $summary['boosted']],
            ] as $item)
                <article class="rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">{{ $item['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $item['value'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-10 flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">Barang terbaru</h2>
            @if ($latestProducts->isNotEmpty())
                <a href="{{ route('seller.products.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Lihat semua</a>
            @endif
        </div>

        @if ($latestProducts->isEmpty())
            <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center">
                <h2 class="text-xl font-semibold">Belum ada barang</h2>
                <p class="mt-2 text-stone-600">Mulai titipkan barang pertamamu untuk ditampilkan di platform.</p>
                <a href="{{ route('seller.products.create') }}" class="button-primary mt-5">Titipkan Barang</a>
            </div>
        @else
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @foreach ($latestProducts as $product)
                    <article class="flex gap-4 rounded-2xl border border-stone-200 bg-white p-4">
                        <div class="size-24 shrink-0 overflow-hidden rounded-xl bg-stone-100">
                            @if ($product->images->first())
                                <img src="{{ Storage::disk($product->images->first()->disk)->url($product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text ?? $product->name }}" class="size-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">{{ $product->category->name }}</p>
                            <h3 class="mt-1 truncate font-semibold">{{ $product->name }}</h3>
                            <p class="mt-1 text-sm text-stone-600">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                            <p class="mt-2 text-xs text-stone-500">
                                @if ($product->status === App\ProductStatus::Hidden)
                                    Disembunyikan
                                @elseif ($product->status === App\ProductStatus::Sold)
                                    Terjual
                                @elseif ($product->published_at)
                                    Aktif
                                @else
                                    Menunggu pembayaran
                                @endif
                            </p>
                            @if ($product->isBoosted())
                                <p class="mt-2 text-xs font-semibold text-emerald-700">BOOSTED sampai {{ $product->active_boost_ends_at->translatedFormat('d M Y H:i') }}</p>
                            @endif
                            <a href="{{ route('seller.products.edit', $product) }}" class="mt-2 inline-block text-sm font-semibold text-emerald-700">Kelola</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
