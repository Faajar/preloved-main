@extends('layouts.app')

@section('title', 'Profil Akun & Kontak WhatsApp')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke Dashboard</a>
        <div class="mt-6">
            <span class="eyebrow">Pengaturan Akun</span>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Profil & Keamanan Akun</h1>
            <p class="mt-2 text-stone-600">Perbarui informasi profil, nomor kontak WhatsApp, serta kata sandi akun Anda.</p>
        </div>

        <div class="mt-8 grid gap-8">
            <!-- Form Informasi Profil -->
            <form method="POST" action="{{ route('profile.update') }}" class="rounded-2xl border border-stone-200 bg-white p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-semibold text-stone-900">Informasi Profil & WhatsApp</h2>
                <p class="mt-1 text-sm text-stone-500">Nomor WhatsApp digunakan untuk tombol kontak pembeli pada barang titipan Anda.</p>

                <div class="mt-6 grid gap-5">
                    <div>
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="255" class="input-field">
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="form-label">Alamat Email</label>
                        <input id="email" type="email" value="{{ $user->email }}" disabled class="input-field bg-stone-50 text-stone-500 cursor-not-allowed">
                        <p class="mt-1 text-xs text-stone-500">Email akun digunakan untuk autentikasi dan bersifat tetap.</p>
                    </div>

                    <div>
                        <label for="whatsapp_number" class="form-label">Nomor WhatsApp Kontak</label>
                        <input id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $user->whatsapp_number ? '0'.substr($user->whatsapp_number, 2) : '') }}" placeholder="081234567890" class="input-field">
                        <p class="mt-1 text-xs text-stone-500">Format Indonesia (contoh: 081234567890 atau +6281234567890).</p>
                        @error('whatsapp_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    @if ($user->whatsapp_number)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            Nomor aktif saat ini: <strong>+{{ $user->whatsapp_number }}</strong>
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Nomor WhatsApp belum diisi. Jika Anda menitipkan barang sebagai seller, nomor ini wajib diisi agar pembeli dapat menghubungi Anda.
                        </div>
                    @endif
                </div>

                <button type="submit" class="button-primary mt-6">Simpan Profil</button>
            </form>

            <!-- Form Ubah Kata Sandi -->
            <form method="POST" action="{{ route('password.change') }}" class="rounded-2xl border border-stone-200 bg-white p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-semibold text-stone-900">Ubah Kata Sandi</h2>
                <p class="mt-1 text-sm text-stone-500">Pastikan akun Anda menggunakan kata sandi yang panjang dan aman.</p>

                <div class="mt-6 grid gap-5">
                    <div>
                        <label for="current_password" class="form-label">Kata Sandi Saat Ini</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="input-field">
                        @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Kata Sandi Baru</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="input-field">
                        @error('password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input-field">
                        @error('password_confirmation')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="button-secondary mt-6">Perbarui Kata Sandi</button>
            </form>
        </div>
    </section>
@endsection
