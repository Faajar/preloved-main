@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="mx-auto max-w-lg px-4 py-12 sm:px-6 sm:py-20">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="eyebrow">Selamat datang kembali</span>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Masuk ke akunmu</h1>
            <p class="mt-2 text-stone-600">Kelola profil dan aktivitasmu di Preloved IN.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 grid gap-5">
                @csrf

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input-field">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="form-label">Kata sandi</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Lupa kata sandi?</a>
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input-field">
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-stone-700">
                    <input name="remember" type="checkbox" value="1" class="size-4 rounded border-stone-300 text-emerald-700 focus:ring-emerald-600">
                    Ingat saya
                </label>

                <button type="submit" class="button-primary justify-center">Masuk</button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-600">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Daftar</a></p>
        </div>
    </section>
@endsection
