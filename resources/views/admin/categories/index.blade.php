@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Admin</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Kelola Kategori</h1>
                <p class="mt-2 text-stone-600">Atur kategori yang tersedia untuk seller dan marketplace publik.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.dashboard') }}" class="button-secondary">Dashboard Admin</a>
                <a href="{{ route('admin.categories.create') }}" class="button-primary">Tambah kategori</a>
            </div>
        </div>

        @if ($categories->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Belum ada kategori</h2>
                <p class="mt-2 text-stone-600">Tambahkan kategori pertama untuk mulai mengelola pilihan listing.</p>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="divide-y divide-stone-200">
                    @foreach ($categories as $category)
                        <a href="{{ route('admin.categories.edit', $category) }}" class="grid gap-3 p-5 transition hover:bg-stone-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold">{{ $category->name }}</h2>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $category->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-700' }}">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-stone-500">/{{ $category->slug }}</p>
                            </div>
                            <span class="text-sm font-medium text-stone-600">{{ $category->products_count }} produk</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="mt-8">{{ $categories->links() }}</div>
        @endif
    </section>
@endsection
