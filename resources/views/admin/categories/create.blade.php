@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.categories.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke kategori</a>
        <span class="eyebrow mt-6 block">Admin kategori</span>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">Tambah Kategori</h1>
        <p class="mt-2 text-stone-600">Kategori baru langsung aktif dan tersedia untuk listing seller.</p>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-8 rounded-2xl border border-stone-200 bg-white p-6">
            @csrf
            @include('admin.categories._form')
            <button type="submit" class="button-primary mt-6">Simpan kategori</button>
        </form>
    </section>
@endsection
