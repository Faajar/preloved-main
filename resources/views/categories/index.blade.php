@extends('layouts.app')

@section('title', 'Semua Kategori Preloved')
@section('meta_description', 'Jelajahi kategori barang preloved aktif di Preloved IN.')
@section('canonical', route('categories.index'))

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <span class="eyebrow">Kategori</span>
        <div class="mt-3 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Jelajahi semua kategori</h1>
                <p class="mt-3 max-w-2xl text-stone-600">Temukan barang preloved berdasarkan kategori yang paling sesuai.</p>
            </div>
            <a href="{{ route('explore') }}" class="button-secondary">Lihat semua listing</a>
        </div>

        @if ($categories->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Belum ada kategori aktif</h2>
                <p class="mt-2 text-stone-600">Kategori akan tampil setelah tersedia.</p>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="rounded-2xl border border-stone-200 bg-white p-6 transition hover:border-emerald-300 hover:bg-emerald-50">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-xl font-semibold">{{ $category->name }}</h2>
                            <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700">{{ $category->public_products_count }} listing</span>
                        </div>
                        @if ($category->description)
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-stone-600">{{ $category->description }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $categories->links() }}</div>
        @endif
    </section>
@endsection
