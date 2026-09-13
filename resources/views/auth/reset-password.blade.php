@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi')

@section('content')
    <section class="mx-auto max-w-lg px-4 py-12 sm:px-6 sm:py-20">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="eyebrow">Keamanan Akun</span>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Atur ulang kata sandi</h1>
            <p class="mt-2 text-stone-600">Masukkan kata sandi baru untuk akun Anda.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-8 grid gap-5">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="email" class="input-field">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="form-label">Kata sandi baru</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input-field">
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Konfirmasi kata sandi baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input-field">
                    @error('password_confirmation')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="button-primary justify-center">Simpan Kata Sandi Baru</button>
            </form>
        </div>
    </section>
@endsection
