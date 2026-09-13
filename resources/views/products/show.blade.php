@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', Str::limit(strip_tags($product->description), 155))
@section('canonical', route('products.show', $product))
@section('og_title', $product->name.' — Preloved IN')
@section('og_description', Str::limit(strip_tags($product->description), 155))
@if ($product->images->first())
    @section('og_image', Storage::disk($product->images->first()->disk)->url($product->images->first()->path))
@endif

@section('content')
    <article class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-stone-500" aria-label="Breadcrumb">
            <a href="{{ route('explore') }}" class="hover:text-emerald-700">Explore</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('categories.show', $product->category) }}" class="hover:text-emerald-700">{{ $product->category->name }}</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700">{{ $product->name }}</span>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-[1.15fr_0.85fr]">
            <section aria-label="Galeri foto {{ $product->name }}">
                @if ($product->images->isEmpty())
                    <div class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-stone-100 text-stone-400">Belum ada foto</div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($product->images as $image)
                            <img src="{{ Storage::disk($image->disk)->url($image->path) }}" alt="{{ $image->alt_text ?? $product->name }}" class="aspect-[4/3] w-full rounded-2xl bg-stone-100 object-cover {{ $loop->first ? 'sm:col-span-2' : '' }}" @if (!$loop->first) loading="lazy" @endif>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="lg:sticky lg:top-28 lg:self-start">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">{{ $product->category->name }}</p>
                    @if ($product->isBoosted())
                        <span class="rounded-full bg-emerald-700 px-3 py-1 text-xs font-bold tracking-wide text-white">BOOSTED</span>
                    @endif
                </div>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950 sm:text-4xl">{{ $product->name }}</h1>
                <p class="mt-5 text-3xl font-semibold">Rp{{ number_format($product->price, 0, ',', '.') }}</p>

                <dl class="mt-7 grid grid-cols-2 gap-4 rounded-2xl border border-stone-200 bg-white p-5 text-sm">
                    <div>
                        <dt class="text-stone-500">Kondisi</dt>
                        <dd class="mt-1 font-semibold">{{ $product->condition->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Lokasi</dt>
                        <dd class="mt-1 font-semibold">{{ $product->location }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Status</dt>
                        <dd class="mt-1 font-semibold">{{ $product->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Tanggal listing</dt>
                        <dd class="mt-1 font-semibold">{{ $product->published_at->translatedFormat('d M Y') }}</dd>
                    </div>
                </dl>

                <section class="mt-7">
                    <h2 class="text-lg font-semibold">Deskripsi</h2>
                    <p class="mt-3 whitespace-pre-line leading-7 text-stone-700">{{ $product->description }}</p>
                </section>

                <section class="mt-7 rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">Dititipkan oleh</p>
                    <a href="{{ route('sellers.show', $product->seller) }}" class="mt-2 block text-lg font-semibold text-stone-950 hover:text-emerald-700">{{ $product->seller->name }}</a>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="button-primary mt-5 w-full justify-center">Chat Penjual</a>
                        <p class="mt-3 text-xs leading-5 text-stone-500">WhatsApp akan terbuka dengan pesan otomatis. Transaksi dilakukan langsung dengan seller di luar platform.</p>
                    @else
                        <p class="mt-3 text-sm text-stone-500">Kontak WhatsApp seller belum tersedia.</p>
                    @endif
                </section>

                <section class="mt-7 border-t border-stone-200 pt-6">
                    <h2 class="text-base font-semibold">Laporkan listing</h2>
                    @guest
                        <p class="mt-2 text-sm text-stone-600"><a href="{{ route('login') }}" class="font-semibold text-emerald-700">Masuk</a> untuk melaporkan listing yang melanggar aturan.</p>
                    @else
                        @can('report', $product)
                            <form method="POST" action="{{ route('products.reports.store', $product) }}" class="mt-4 grid gap-4">
                                @csrf
                                <div>
                                    <label for="reason" class="form-label">Alasan</label>
                                    <select id="reason" name="reason" required class="input-field">
                                        <option value="">Pilih alasan</option>
                                        @foreach (App\ReportReason::cases() as $reason)
                                            <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="report_description" class="form-label">Penjelasan</label>
                                    <textarea id="report_description" name="description" rows="3" maxlength="2000" class="input-field" placeholder="Jelaskan masalah yang kamu temukan">{{ old('description') }}</textarea>
                                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                                    @error('report')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                <button type="submit" class="button-secondary justify-center">Kirim laporan</button>
                            </form>
                        @else
                            <p class="mt-2 text-sm text-stone-500">Kamu tidak dapat melaporkan listing ini.</p>
                        @endcan
                    @endguest
                </section>
            </div>
        </div>
    </article>
@endsection
