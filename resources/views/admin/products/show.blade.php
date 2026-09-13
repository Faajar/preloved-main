@extends('layouts.app')

@section('title', 'Moderasi '.$product->name)

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke daftar moderasi produk</a>
        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Detail moderasi produk</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ $product->name }}</h1>
                <p class="mt-2 text-stone-600">
                    Kategori: {{ $product->category->name }} · 
                    Status: <strong class="text-stone-900">{{ $product->status->label() }}</strong>
                    @if ($product->trashed()) (Dihapus seller) @endif
                </p>
            </div>
            <time class="text-sm text-stone-500" datetime="{{ $product->created_at->toAtomString() }}">{{ $product->created_at->translatedFormat('d M Y H:i') }}</time>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Informasi Produk</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-stone-500">Harga</dt>
                            <dd class="text-xl font-bold text-stone-950">Rp{{ number_format($product->price, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Kondisi</dt>
                            <dd class="font-medium text-stone-800">{{ $product->condition->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Lokasi</dt>
                            <dd class="font-medium text-stone-800">{{ $product->location }}</dd>
                        </div>
                        <div>
                            <dt class="text-stone-500">Deskripsi</dt>
                            <dd class="mt-1 whitespace-pre-line leading-6 text-stone-700 bg-stone-50 p-3 rounded-xl">{{ $product->description }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Foto Barang ({{ $product->images->count() }})</h2>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        @forelse ($product->images as $image)
                            <div class="relative aspect-square overflow-hidden rounded-xl border border-stone-200 bg-stone-100">
                                <img src="{{ Storage::disk($image->disk)->url($image->path) }}" alt="{{ $image->alt_text }}" class="size-full object-cover">
                                @if ($image->is_primary)
                                    <span class="absolute bottom-1 left-1 rounded bg-stone-900/80 px-1.5 py-0.5 text-[10px] font-semibold text-white">Utama</span>
                                @endif
                            </div>
                        @empty
                            <p class="col-span-3 text-sm text-stone-500">Tidak ada foto.</p>
                        @endforelse
                    </div>
                </article>

                @unless ($product->trashed())
                    <article class="rounded-2xl border {{ $product->status === App\ProductStatus::Hidden ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-6">
                        <h2 class="text-lg font-semibold {{ $product->status === App\ProductStatus::Hidden ? 'text-emerald-950' : 'text-red-950' }}">
                            {{ $product->status === App\ProductStatus::Hidden ? 'Tampilkan Kembali (Unhide) Produk' : 'Sembunyikan (Hide) Produk Ini' }}
                        </h2>
                        <p class="mt-1 text-sm {{ $product->status === App\ProductStatus::Hidden ? 'text-emerald-800' : 'text-red-800' }}">
                            {{ $product->status === App\ProductStatus::Hidden 
                                ? 'Kembalikan status produk menjadi Available sehingga tampil kembali di marketplace publik.' 
                                : 'Produk tidak akan muncul di pencarian, homepage, atau profil seller. Gunakan untuk konten terlarang, spam, atau penipuan.' }}
                        </p>

                        <form method="POST" action="{{ route('admin.products.update', $product->id) }}" class="mt-5">
                            @csrf
                            @method('PATCH')

                            <input type="hidden" name="status" value="{{ $product->status === App\ProductStatus::Hidden ? App\ProductStatus::Available->value : App\ProductStatus::Hidden->value }}">

                            <div>
                                <label for="reason" class="form-label">Alasan tindakan moderasi</label>
                                <textarea id="reason" name="reason" rows="3" maxlength="1000" required class="input-field" placeholder="Contoh: Barang terindikasi palsu / melanggar ketentuan platform">{{ old('reason') }}</textarea>
                                @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit" class="mt-4 {{ $product->status === App\ProductStatus::Hidden ? 'button-primary' : 'inline-flex items-center justify-center rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800' }}">
                                {{ $product->status === App\ProductStatus::Hidden ? 'Aktifkan Kembali Produk' : 'Sembunyikan Produk Ini' }}
                            </button>
                        </form>
                    </article>
                @endunless
            </div>

            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Informasi Seller</h2>
                    <dl class="mt-4 grid gap-2 text-sm">
                        <div><dt class="text-stone-500">Nama</dt><dd class="font-medium text-stone-900">{{ $product->seller->name }}</dd></div>
                        <div><dt class="text-stone-500">Email</dt><dd class="font-medium text-stone-900">{{ $product->seller->email }}</dd></div>
                        <div><dt class="text-stone-500">WhatsApp Profil</dt><dd class="font-medium text-stone-900">{{ $product->seller->whatsapp_number ? '+'.$product->seller->whatsapp_number : 'Belum diisi' }}</dd></div>
                    </dl>
                </article>

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Laporan Terkait ({{ $product->reports->count() }})</h2>
                    </div>
                    @if ($product->reports->isEmpty())
                        <p class="mt-3 text-sm text-stone-500">Tidak ada laporan pengguna terhadap produk ini.</p>
                    @else
                        <div class="mt-3 divide-y divide-stone-100">
                            @foreach ($product->reports as $rep)
                                <div class="py-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-stone-800">#{{ $rep->id }} · {{ $rep->reason->label() }}</span>
                                        <span class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-600">{{ $rep->status->label() }}</span>
                                    </div>
                                    @if ($rep->description)
                                        <p class="mt-1 text-xs text-stone-600">{{ $rep->description }}</p>
                                    @endif
                                    <a href="{{ route('admin.reports.show', $rep) }}" class="mt-1 inline-block text-xs font-medium text-emerald-700">Lihat detail laporan →</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Histori Moderasi Produk</h2>
                    @if ($product->moderationAudits->isEmpty())
                        <p class="mt-3 text-sm text-stone-500">Belum ada catatan tindakan moderasi langsung.</p>
                    @else
                        <div class="mt-4 grid gap-3">
                            @foreach ($product->moderationAudits as $aud)
                                <div class="border-b border-stone-100 pb-3 last:border-0 last:pb-0 text-sm">
                                    <p class="font-semibold text-stone-900">{{ $aud->action }}</p>
                                    <p class="text-xs text-stone-500">{{ $aud->actor->name }} · {{ $aud->created_at->translatedFormat('d M Y H:i') }}</p>
                                    @if ($aud->reason)
                                        <p class="mt-1.5 rounded-lg bg-stone-50 p-2 text-xs text-stone-700">{{ $aud->reason }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </div>
    </section>
@endsection
