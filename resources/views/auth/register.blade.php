@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
    <section class="mx-auto max-w-lg px-4 py-12 sm:px-6 sm:py-20">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="eyebrow">Buat akun</span>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Bergabung dengan Preloved IN</h1>
            <p class="mt-2 text-stone-600">Akun baru dibuat sebagai buyer. Kamu dapat mengaktifkan seller kapan saja.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 grid gap-5">
                @csrf

                <div>
                    <label for="name" class="form-label">Nama</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="input-field">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input-field">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="form-label">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input-field">
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Konfirmasi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input-field">
                </div>

                <button type="submit" class="button-primary justify-center">Daftar</button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Masuk</a></p>
        </div>
    </section>
@endsection
