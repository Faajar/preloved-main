@extends('layouts.app')

@section('title', 'Edit '.$category->name)

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.categories.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke kategori</a>
        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Admin kategori</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ $category->name }}</h1>
                <p class="mt-2 text-stone-600">/{{ $category->slug }} · {{ $category->products_count }} produk · {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</p>
            </div>
            @if ($category->is_active)
                <a href="{{ route('categories.show', $category) }}" class="button-secondary">Lihat halaman publik</a>
            @endif
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.8fr]">
            <div class="grid content-start gap-6">
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="rounded-2xl border border-stone-200 bg-white p-6">
                    @csrf
                    @method('PUT')
                    <h2 class="text-lg font-semibold">Informasi kategori</h2>
                    <div class="mt-5">@include('admin.categories._form')</div>
                    <button type="submit" class="button-primary mt-6">Simpan perubahan</button>
                </form>

                <form method="POST" action="{{ route('admin.categories.activation.update', $category) }}" class="rounded-2xl border {{ $category->is_active ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-6">
                    @csrf
                    @method('PATCH')
                    <h2 class="text-lg font-semibold">{{ $category->is_active ? 'Nonaktifkan kategori' : 'Aktifkan kategori' }}</h2>
                    <p class="mt-2 text-sm text-stone-700">
                        {{ $category->is_active ? 'Listing terkait akan hilang dari marketplace tanpa menghapus datanya.' : 'Kategori kembali tersedia untuk seller dan listing terkait dapat tampil publik.' }}
                    </p>
                    <label for="activation_reason" class="form-label mt-5">Alasan</label>
                    <textarea id="activation_reason" name="reason" rows="3" maxlength="1000" required class="input-field"></textarea>
                    @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                    <button type="submit" class="button-secondary mt-5">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>

                @if ($category->products_count === 0)
                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="rounded-2xl border border-red-200 bg-red-50 p-6">
                        @csrf
                        @method('DELETE')
                        <h2 class="text-lg font-semibold text-red-900">Hapus kategori kosong</h2>
                        <p class="mt-2 text-sm text-red-800">Kategori yang sudah memiliki produk tidak dapat dihapus.</p>
                        <label for="delete_reason" class="form-label mt-5">Alasan penghapusan</label>
                        <textarea id="delete_reason" name="reason" rows="3" maxlength="1000" required class="input-field"></textarea>
                        @error('category')<p class="form-error">{{ $message }}</p>@enderror
                        <button type="submit" class="mt-5 inline-flex items-center justify-center rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">Hapus permanen</button>
                    </form>
                @endif
            </div>

            <aside class="rounded-2xl border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Histori audit</h2>
                @if ($audits->isEmpty())
                    <p class="mt-4 text-sm text-stone-600">Belum ada histori perubahan.</p>
                @else
                    <div class="mt-5 grid gap-4">
                        @foreach ($audits as $audit)
                            <article class="border-b border-stone-200 pb-4 last:border-0 last:pb-0">
                                <p class="text-sm font-semibold">{{ $audit->action }}</p>
                                <p class="mt-1 text-sm text-stone-600">{{ $audit->actor->name }} · {{ $audit->created_at->translatedFormat('d M Y H:i') }}</p>
                                @if ($audit->reason)
                                    <p class="mt-2 whitespace-pre-line text-sm text-stone-700">{{ $audit->reason }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
