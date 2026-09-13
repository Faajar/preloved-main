@extends('layouts.app')

@section('title', 'Titipkan Barang')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('seller.products.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke Barang Saya</a>
        <span class="eyebrow mt-6 block">Listing baru</span>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">Titipkan Barang</h1>
        <p class="mt-2 text-stone-600">Lengkapi informasi barang. Saat launch mode gratis aktif, listing langsung dipublikasikan.</p>

        <form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data" class="mt-8 rounded-2xl border border-stone-200 bg-white p-6 sm:p-8">
            @csrf
            @include('seller.products._form')
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('seller.products.index') }}" class="button-secondary justify-center">Batal</a>
                <button type="submit" class="button-primary justify-center">Simpan listing</button>
            </div>
        </form>
    </section>
@endsection
