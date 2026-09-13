@extends('layouts.app')

@section('title', 'Kelola '.$product->name)

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('seller.products.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke Barang Saya</a>

        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Kelola listing</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ $product->name }}</h1>
                <p class="mt-2 text-stone-600">
                    {{ $product->status->label() }} · {{ $product->published_at ? 'Listing aktif' : 'Menunggu pembayaran listing' }}
                </p>
            </div>
            @if ($product->status === App\ProductStatus::Available && $product->published_at)
                <form method="POST" action="{{ route('seller.products.sold', $product) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="button-secondary">Tandai terjual</button>
                </form>
            @endif
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_0.8fr]">
            <form method="POST" action="{{ route('seller.products.update', $product) }}" class="rounded-2xl border border-stone-200 bg-white p-6 sm:p-8">
                @csrf
                @method('PUT')
                @include('seller.products._form')
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="button-primary">Simpan perubahan</button>
                </div>
            </form>

            <div class="grid content-start gap-6">
                @php($listingPayment = $product->payments->firstWhere('type', App\PaymentType::Listing))
                @if (!$product->published_at && $listingPayment)
                    <section class="rounded-2xl border border-amber-200 bg-white p-6">
                        <h2 class="font-semibold text-amber-900">Pembayaran listing</h2>
                        <p class="mt-2 text-sm text-stone-600">Status: {{ $listingPayment->status->label() }} · Rp{{ number_format($listingPayment->amount, 0, ',', '.') }}</p>
                        <a href="{{ route('seller.payments.show', $listingPayment) }}" class="button-primary mt-4">Lihat dan bayar dengan DOKU</a>
                    </section>
                @endif

                <section class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Foto barang</h2>
                    <p class="mt-1 text-sm text-stone-500">Maksimal lima foto. Foto utama tampil pertama.</p>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        @foreach ($product->images as $image)
                            <article class="overflow-hidden rounded-xl border {{ $image->is_primary ? 'border-emerald-600' : 'border-stone-200' }}">
                                <img src="{{ Storage::disk($image->disk)->url($image->path) }}" alt="{{ $image->alt_text ?? $product->name }}" class="aspect-square w-full object-cover">
                                <div class="grid gap-2 p-3">
                                    @if ($image->is_primary)
                                        <span class="text-xs font-semibold text-emerald-700">Foto utama</span>
                                    @else
                                        <form method="POST" action="{{ route('seller.products.images.primary', [$product, $image]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-left text-xs font-semibold text-emerald-700">Jadikan utama</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('seller.products.images.destroy', [$product, $image]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-left text-xs font-semibold text-red-700">Hapus foto</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ($product->images->count() < 5)
                        <form method="POST" action="{{ route('seller.products.images.store', $product) }}" enctype="multipart/form-data" class="mt-5 border-t border-stone-200 pt-5">
                            @csrf
                            <label for="images" class="form-label">Tambah foto</label>
                            <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="input-field">
                            @error('images')<p class="form-error">{{ $message }}</p>@enderror
                            @error('images.*')<p class="form-error">{{ $message }}</p>@enderror
                            <button type="submit" class="button-secondary mt-3">Upload foto</button>
                        </form>
                    @endif
                </section>

                <section class="rounded-2xl border border-emerald-200 bg-white p-6">
                    <h2 class="font-semibold text-emerald-900">Boost listing</h2>
                    @if ($product->isBoosted())
                        <span class="mt-3 inline-flex rounded-full bg-emerald-700 px-3 py-1 text-xs font-bold tracking-wide text-white">BOOSTED</span>
                        <p class="mt-3 text-sm text-stone-600">Aktif sampai {{ $product->active_boost_ends_at->translatedFormat('d M Y H:i') }}.</p>
                    @elseif (!$settings->boost_duration_hours)
                        <p class="mt-3 text-sm text-stone-600">Durasi boost belum dikonfigurasi. Boost belum dapat diaktifkan.</p>
                    @elseif (!$canBoostProduct)
                        <p class="mt-3 text-sm text-stone-600">Hanya listing aktif dan terlihat publik yang dapat di-boost.</p>
                    @elseif ($settings->boost_fee_enabled)
                        <p class="mt-3 text-sm text-stone-600">Prioritaskan listing selama {{ $settings->boost_duration_hours }} jam dengan biaya Rp{{ number_format($settings->boost_fee, 0, ',', '.') }}.</p>
                        <form method="POST" action="{{ route('seller.products.boosts.store', $product) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="button-primary">Bayar boost dengan DOKU</button>
                        </form>
                    @elseif ($hasOtherActiveBoost)
                        <p class="mt-3 text-sm text-stone-600">Kamu sudah memiliki boost aktif untuk barang lain.</p>
                    @else
                        <p class="mt-3 text-sm text-stone-600">Prioritaskan listing selama {{ $settings->boost_duration_hours }} jam. Satu seller dapat memiliki satu boost aktif.</p>
                        <form method="POST" action="{{ route('seller.products.boosts.store', $product) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="button-primary">Aktifkan boost gratis</button>
                        </form>
                    @endif
                    @error('boost')<p class="form-error">{{ $message }}</p>@enderror
                    @error('product')<p class="form-error">{{ $message }}</p>@enderror
                </section>

                <section class="rounded-2xl border border-red-200 bg-white p-6">
                    <h2 class="font-semibold text-red-800">Hapus listing</h2>
                    <p class="mt-2 text-sm text-stone-600">Listing akan dihapus secara soft delete. Foto dipertahankan untuk audit.</p>
                    <form method="POST" action="{{ route('seller.products.destroy', $product) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex min-h-10 items-center rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Hapus barang</button>
                    </form>
                </section>
            </div>
        </div>
    </section>
@endsection
