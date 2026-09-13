@extends('layouts.app')

@section('title', 'Moderasi Produk')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Admin moderasi</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Moderasi Listing Produk</h1>
                <p class="mt-2 text-stone-600">Pantau seluruh listing barang, tinjau konten bermasalah, dan lakukan tindakan sembunyikan (hide) jika melanggar aturan.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="button-secondary">Dashboard Admin</a>
        </div>

        <form method="GET" action="{{ route('admin.products.index') }}" class="mt-8 grid gap-3 rounded-2xl border border-stone-200 bg-white p-4 sm:grid-cols-4 sm:items-end">
            <div>
                <label for="q" class="form-label">Cari nama / seller</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" placeholder="Ketik kata kunci..." class="input-field">
            </div>

            <div>
                <label for="status" class="form-label">Filter status</label>
                <select id="status" name="status" class="input-field">
                    <option value="">Semua status</option>
                    @foreach (App\ProductStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                    <option value="trashed" @selected(request('status') === 'trashed')>Dihapus seller</option>
                </select>
            </div>

            <div>
                <label for="category_id" class="form-label">Filter kategori</label>
                <select id="category_id" name="category_id" class="input-field">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="button-primary flex-1 justify-center">Terapkan</button>
                <a href="{{ route('admin.products.index') }}" class="button-secondary justify-center">Reset</a>
            </div>
        </form>

        @if ($products->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Tidak ada produk ditemukan</h2>
                <p class="mt-2 text-stone-600">Sesuaikan filter atau kata kunci pencarian Anda.</p>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="divide-y divide-stone-200">
                    @foreach ($products as $product)
                        <a href="{{ route('admin.products.show', $product->id) }}" class="grid gap-4 p-5 transition hover:bg-stone-50 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                            <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-stone-100 border border-stone-200">
                                @if ($product->primaryImage)
                                    <img src="{{ Storage::disk($product->primaryImage->disk)->url($product->primaryImage->path) }}" alt="{{ $product->name }}" class="size-full object-cover">
                                @else
                                    <div class="flex size-full items-center justify-center text-xs text-stone-400">No img</div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold truncate text-stone-900">{{ $product->name }}</h2>
                                    @if ($product->trashed())
                                        <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">Dihapus seller</span>
                                    @elseif ($product->status === App\ProductStatus::Hidden)
                                        <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Hidden</span>
                                    @elseif ($product->status === App\ProductStatus::Sold)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-700">Sold</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Available</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-stone-500">
                                    Kategori: <span class="font-medium text-stone-700">{{ $product->category->name }}</span> · 
                                    Seller: <span class="font-medium text-stone-700">{{ $product->seller->name }}</span> ({{ $product->seller->email }})
                                </p>
                                <p class="mt-1 text-sm font-semibold text-stone-900">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                            </div>

                            <div class="text-right">
                                <time class="text-xs text-stone-500" datetime="{{ $product->created_at->toAtomString() }}">{{ $product->created_at->translatedFormat('d M Y H:i') }}</time>
                                <p class="mt-2 text-xs font-medium text-emerald-700">Detail moderasi →</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
