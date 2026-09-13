@extends('layouts.app')

@section('title', 'Pengaturan Monetisasi & Boost')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke Dashboard Admin</a>
        <div class="mt-6">
            <span class="eyebrow">Admin pengaturan</span>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Pengaturan Monetisasi & Boost</h1>
            <p class="mt-2 text-stone-600">Atur tarif listing, tarif boost, durasi boost aktif, dan mode gratis peluncuran platform.</p>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.8fr]">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="rounded-2xl border border-stone-200 bg-white p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-semibold">Konfigurasi Tarif</h2>

                <div class="mt-6 grid gap-5">
                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                        <label class="flex items-center gap-3">
                            <input type="hidden" name="listing_fee_enabled" value="0">
                            <input type="checkbox" name="listing_fee_enabled" value="1" @checked(old('listing_fee_enabled', $setting->listing_fee_enabled)) class="size-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-medium text-stone-900">Aktifkan Biaya Listing Berbayar</span>
                        </label>
                        <p class="mt-1 text-xs text-stone-500">Jika dinonaktifkan, seller dapat mempublikasikan barang secara gratis tanpa pembayaran.</p>
                        @error('listing_fee_enabled')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="listing_fee" class="form-label">Nominal Biaya Listing (Rp)</label>
                        <input id="listing_fee" name="listing_fee" type="number" min="0" step="100" value="{{ old('listing_fee', $setting->listing_fee) }}" required class="input-field">
                        <p class="mt-1 text-xs text-stone-500">Standar aturan platform: Rp3.000 per barang.</p>
                        @error('listing_fee')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                        <label class="flex items-center gap-3">
                            <input type="hidden" name="boost_fee_enabled" value="0">
                            <input type="checkbox" name="boost_fee_enabled" value="1" @checked(old('boost_fee_enabled', $setting->boost_fee_enabled)) class="size-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="font-medium text-stone-900">Aktifkan Biaya Boost Berbayar</span>
                        </label>
                        <p class="mt-1 text-xs text-stone-500">Jika dinonaktifkan, seller dapat mengaktifkan boost secara gratis selama kuota 1 boost aktif per seller terpenuhi.</p>
                        @error('boost_fee_enabled')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="boost_fee" class="form-label">Nominal Biaya Boost (Rp)</label>
                        <input id="boost_fee" name="boost_fee" type="number" min="0" step="100" value="{{ old('boost_fee', $setting->boost_fee) }}" required class="input-field">
                        <p class="mt-1 text-xs text-stone-500">Standar aturan platform: Rp3.000 per boost.</p>
                        @error('boost_fee')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="boost_duration_hours" class="form-label">Durasi Masa Aktif Boost (Jam)</label>
                        <input id="boost_duration_hours" name="boost_duration_hours" type="number" min="1" max="720" value="{{ old('boost_duration_hours', $setting->boost_duration_hours ?? 24) }}" required class="input-field">
                        <p class="mt-1 text-xs text-stone-500">Contoh: 24 jam = 1 hari, 72 jam = 3 hari, 168 jam = 7 hari.</p>
                        @error('boost_duration_hours')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="reason" class="form-label">Alasan Perubahan</label>
                        <textarea id="reason" name="reason" rows="3" maxlength="1000" required class="input-field" placeholder="Contoh: Mengatur durasi boost 24 jam untuk peluncuran beta">{{ old('reason') }}</textarea>
                        <p class="mt-1 text-xs text-stone-500">Wajib diisi untuk catatan audit moderasi platform.</p>
                        @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="button-primary mt-6">Simpan Pengaturan</button>
            </form>

            <aside class="rounded-2xl border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Histori Audit Pengaturan</h2>
                @if ($audits->isEmpty())
                    <p class="mt-4 text-sm text-stone-600">Belum ada histori perubahan pengaturan.</p>
                @else
                    <div class="mt-5 grid gap-4">
                        @foreach ($audits as $audit)
                            <article class="border-b border-stone-200 pb-4 last:border-0 last:pb-0">
                                <p class="text-sm font-semibold">{{ $audit->action }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $audit->actor->name }} · {{ $audit->created_at->translatedFormat('d M Y H:i') }}</p>
                                @if ($audit->reason)
                                    <p class="mt-2 text-xs leading-5 text-stone-700 bg-stone-50 p-2 rounded-lg">{{ $audit->reason }}</p>
                                @endif
                                @if ($audit->after)
                                    <div class="mt-2 text-xs text-stone-600">
                                        <p>Listing: {{ ($audit->after['listing_fee_enabled'] ?? false) ? 'Berbayar (Rp'.number_format($audit->after['listing_fee'] ?? 0, 0, ',', '.').')' : 'Gratis' }}</p>
                                        <p>Boost: {{ ($audit->after['boost_fee_enabled'] ?? false) ? 'Berbayar (Rp'.number_format($audit->after['boost_fee'] ?? 0, 0, ',', '.').')' : 'Gratis' }} ({{ $audit->after['boost_duration_hours'] ?? 0 }} jam)</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
