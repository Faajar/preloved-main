@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <span class="eyebrow">Dashboard {{ auth()->user()->role->label() }}</span>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Halo, {{ auth()->user()->name }}.</h1>
        <p class="mt-3 max-w-2xl text-stone-600">Kelola akun, profil WhatsApp, dan akses peran Anda di platform Preloved IN.</p>

        <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-2xl border border-stone-200 bg-white p-6">
                <p class="text-sm font-medium text-stone-500">Role Akun</p>
                <p class="mt-2 text-2xl font-semibold text-stone-900">{{ auth()->user()->role->label() }}</p>
                <p class="mt-3 text-sm leading-6 text-stone-600">
                    @if (auth()->user()->isBuyer())
                        Sebagai buyer, Anda dapat menjelajahi barang preloved dan menghubungi penjual via WhatsApp.
                    @elseif (auth()->user()->isAdmin())
                        Sebagai admin, Anda memiliki akses penuh moderasi laporan, produk, kategori, dan pembayaran.
                    @else
                        Sebagai seller, Anda memiliki akses penuh menitipkan barang dan mengelola listing preloved.
                    @endif
                </p>
            </article>

            <article class="rounded-2xl border border-stone-200 bg-white p-6">
                <p class="text-sm font-medium text-stone-500">Profil & Kontak WhatsApp</p>
                <p class="mt-2 text-lg font-semibold text-stone-900">
                    {{ auth()->user()->whatsapp_number ? '+'.auth()->user()->whatsapp_number : 'Belum diatur' }}
                </p>
                <p class="mt-2 text-xs text-stone-500">Nomor ini digunakan untuk tombol chat pembeli pada barang titipan Anda.</p>
                <a href="{{ route('profile.edit') }}" class="button-secondary mt-4 inline-block text-xs">Ubah Profil & WhatsApp →</a>
            </article>

            @if (auth()->user()->isBuyer())
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 md:col-span-2 lg:col-span-1">
                    <p class="text-sm font-semibold text-emerald-800">Siap menjual barang?</p>
                    <h2 class="mt-2 text-xl font-semibold text-stone-950">Aktifkan akun seller</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-700">Aktivasi gratis ini mengubah role akun menjadi seller dan tetap mempertahankan seluruh kemampuan buyer.</p>
                    <form method="POST" action="{{ route('seller.activate') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="button-primary">Aktifkan Akun Seller</button>
                    </form>
                </article>
            @elseif (auth()->user()->isAdmin())
                <article class="rounded-2xl border border-stone-200 bg-white p-6 md:col-span-2 lg:col-span-1">
                    <h2 class="text-xl font-semibold">Administrasi Platform</h2>
                    <p class="mt-2 text-sm text-stone-600">Akses pusat kendali moderasi laporan, listing, kategori, dan pembayaran.</p>
                    <a href="{{ route('admin.dashboard') }}" class="button-primary mt-5 inline-block">Buka Dashboard Admin</a>
                </article>
            @else
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6 md:col-span-2 lg:col-span-1">
                    <h2 class="text-xl font-semibold text-stone-950">Area Seller</h2>
                    <p class="mt-2 text-sm text-stone-700">Kelola barang titipan Anda, tambah foto, lakukan boost, atau pantau status pembayaran.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('seller.dashboard') }}" class="button-primary text-xs">Dashboard Seller</a>
                        <a href="{{ route('seller.products.create') }}" class="button-secondary text-xs">Titipkan Barang</a>
                    </div>
                </article>
            @endif
        </div>
    </section>
@endsection
