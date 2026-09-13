@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Admin pengguna</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Kelola Pengguna</h1>
                <p class="mt-2 text-stone-600">Daftar akun buyer, seller, dan admin serta penanganan akun bermasalah.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="button-secondary">Dashboard Admin</a>
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="mt-8 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 sm:grid-cols-4 sm:items-end">
            <div>
                <label for="q" class="form-label">Cari nama / email / WA</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" placeholder="Ketik kata kunci..." class="input-field">
            </div>

            <div>
                <label for="role" class="form-label">Filter Role</label>
                <select id="role" name="role" class="input-field">
                    <option value="">Semua role</option>
                    @foreach (App\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="form-label">Status Moderasi</label>
                <select id="status" name="status" class="input-field">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif normal</option>
                    <option value="warned" @selected(request('status') === 'warned')>Mendapat peringatan</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Ditangguhkan (Suspended)</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="button-primary flex-1 justify-center">Terapkan</button>
                <a href="{{ route('admin.users.index') }}" class="button-secondary justify-center">Reset</a>
            </div>
        </form>

        @if ($users->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Tidak ada pengguna ditemukan</h2>
                <p class="mt-2 text-stone-600">Sesuaikan filter atau kata kunci pencarian Anda.</p>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="divide-y divide-stone-200">
                    @foreach ($users as $user)
                        <a href="{{ route('admin.users.show', $user) }}" class="grid gap-3 p-5 transition hover:bg-stone-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-stone-900">{{ $user->name }}</span>
                                    <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-700">{{ $user->role->label() }}</span>
                                    @if ($user->isSuspended())
                                        <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                            {{ $user->isPermanentlySuspended() ? 'Permanently Suspended' : 'Suspended' }}
                                        </span>
                                    @elseif ($user->warning_count > 0)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                            {{ $user->warning_count }} Peringatan
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-stone-500">
                                    {{ $user->email }}
                                    @if ($user->whatsapp_number)
                                        · WA: +{{ $user->whatsapp_number }}
                                    @endif
                                    · {{ $user->products_count }} listing · {{ $user->reports_count }} laporan diajukan
                                </p>
                            </div>

                            <div class="text-right">
                                <time class="text-xs text-stone-500" datetime="{{ $user->created_at->toAtomString() }}">Gabung: {{ $user->created_at->translatedFormat('d M Y') }}</time>
                                <p class="mt-1 text-xs font-medium text-emerald-700">Detail & Moderasi →</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="mt-8">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
