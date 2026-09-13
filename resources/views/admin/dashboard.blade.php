@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <span class="eyebrow">Admin area</span>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Dashboard Admin</h1>
        <p class="mt-3 max-w-2xl text-stone-600">Pusat kendali moderasi platform, pemantauan transaksi fee, pengelolaan kategori, dan pengaturan sistem.</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.users.index') }}" class="group rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-stone-500">Pengguna Terdaftar</p>
                    <span class="text-xs text-emerald-700 font-semibold group-hover:underline">Kelola →</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-stone-900">{{ number_format($userMetrics['total']) }}</p>
                <p class="mt-2 text-xs text-stone-500">{{ $userMetrics['buyers'] }} buyer · {{ $userMetrics['sellers'] }} seller</p>
            </a>

            <a href="{{ route('admin.products.index') }}" class="group rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-stone-500">Listing Barang</p>
                    <span class="text-xs text-emerald-700 font-semibold group-hover:underline">Moderasi →</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-stone-900">{{ number_format($productMetrics['total']) }}</p>
                <p class="mt-2 text-xs text-stone-500">{{ $productMetrics['available'] }} aktif · {{ $productMetrics['sold'] }} sold · {{ $productMetrics['hidden'] }} hidden</p>
            </a>

            <a href="{{ route('admin.reports.index') }}" class="group rounded-2xl border {{ $reportMetrics['pending'] > 0 ? 'border-amber-200 bg-amber-50/70' : 'border-stone-200 bg-white' }} p-5 transition hover:border-amber-400">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium {{ $reportMetrics['pending'] > 0 ? 'text-amber-800' : 'text-stone-500' }}">Laporan Pengguna</p>
                    <span class="text-xs font-semibold {{ $reportMetrics['pending'] > 0 ? 'text-amber-900' : 'text-stone-700' }} group-hover:underline">Tinjau →</span>
                </div>
                <p class="mt-2 text-3xl font-bold {{ $reportMetrics['pending'] > 0 ? 'text-amber-900' : 'text-stone-900' }}">
                    {{ number_format($reportMetrics['pending']) }} <span class="text-xs font-normal text-stone-500">pending</span>
                </p>
                <p class="mt-2 text-xs text-stone-500">{{ $reportMetrics['total'] }} total laporan</p>
            </a>

            <a href="{{ route('admin.payments.index') }}" class="group rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-stone-500">Total Pendapatan Fee</p>
                    <span class="text-xs text-emerald-700 font-semibold group-hover:underline">Detail →</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-emerald-800">Rp{{ number_format($paymentMetrics['total_revenue'], 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-stone-500">{{ $paymentMetrics['paid_count'] }} paid · {{ $paymentMetrics['pending_count'] }} pending</p>
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <a href="{{ route('admin.categories.index') }}" class="flex items-center justify-between rounded-2xl border border-stone-200 bg-white p-6 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Kelola Kategori</h2>
                    <p class="mt-1 text-sm text-stone-600">{{ $categoriesCount }} kategori aktif & terdaftar untuk marketplace.</p>
                </div>
                <span class="text-sm font-semibold text-emerald-700">Buka kategori →</span>
            </a>

            <a href="{{ route('admin.settings.edit') }}" class="flex items-center justify-between rounded-2xl border border-stone-200 bg-white p-6 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Pengaturan Monetisasi & Boost</h2>
                    <p class="mt-1 text-sm text-stone-600">
                        Listing: {{ ($settings?->listing_fee_enabled ?? false) ? 'Berbayar' : 'Gratis' }} · 
                        Boost: {{ ($settings?->boost_fee_enabled ?? false) ? 'Berbayar' : 'Gratis' }} ({{ $settings?->boost_duration_hours ?? 24 }} jam)
                    </p>
                </div>
                <span class="text-sm font-semibold text-emerald-700">Ubah tarif →</span>
            </a>
        </div>
    </section>
@endsection
