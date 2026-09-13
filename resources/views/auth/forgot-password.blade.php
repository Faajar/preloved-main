@extends('layouts.app')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <section class="mx-auto max-w-lg px-4 py-12 sm:px-6 sm:py-20">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="eyebrow">Pemulihan Akun</span>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Lupa kata sandi?</h1>
            <p class="mt-2 text-stone-600 leading-6">
                Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan reset kata sandi ke email Anda.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="mt-8 grid gap-5">
                @csrf

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input-field">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="button-primary justify-center">Kirim Tautan Reset</button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-600">
                Ingat kata sandi Anda? <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800">Masuk</a>
            </p>
        </div>
    </section>
@endsection
