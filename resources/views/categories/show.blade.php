@extends('layouts.app')

@section('title', $category->name.' Preloved')
@section('meta_description', 'Temukan listing barang preloved kategori '.$category->name.' di Preloved IN.')
@section('canonical', route('categories.show', $category))

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm font-semibold text-emerald-700">
            <a href="{{ route('categories.index') }}">Semua kategori</a>
            <span aria-hidden="true" class="text-stone-400">/</span>
            <span aria-current="page" class="text-stone-600">{{ $category->name }}</span>
        </nav>
        <span class="eyebrow mt-6 block">Kategori</span>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-3 max-w-2xl text-stone-600">{{ $category->description }}</p>
        @endif

        @if ($products->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Belum ada barang di kategori ini</h2>
                <p class="mt-2 text-stone-600">Jelajahi kategori lain atau kembali lagi nanti.</p>
                <a href="{{ route('explore') }}" class="button-secondary mt-5">Lihat semua barang</a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :$product />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
