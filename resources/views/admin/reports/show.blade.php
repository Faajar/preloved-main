@extends('layouts.app')

@section('title', 'Laporan #'.$report->id)

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke laporan</a>
        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Moderasi laporan</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Laporan #{{ $report->id }}</h1>
                <p class="mt-2 text-stone-600">{{ $report->reason->label() }} · {{ $report->status->label() }}</p>
            </div>
            <time class="text-sm text-stone-500" datetime="{{ $report->created_at->toAtomString() }}">{{ $report->created_at->translatedFormat('d M Y H:i') }}</time>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Detail laporan</h2>
                    <dl class="mt-5 grid gap-4 text-sm">
                        <div><dt class="text-stone-500">Alasan</dt><dd class="mt-1 font-medium">{{ $report->reason->label() }}</dd></div>
                        <div><dt class="text-stone-500">Pelapor</dt><dd class="mt-1 font-medium">{{ $report->reporter->name }} · {{ $report->reporter->email }}</dd></div>
                        <div><dt class="text-stone-500">Penjelasan</dt><dd class="mt-1 whitespace-pre-line leading-6">{{ $report->description ?: 'Tidak ada penjelasan tambahan.' }}</dd></div>
                    </dl>
                </article>

                @if (in_array($report->status, [App\ReportStatus::Pending, App\ReportStatus::Reviewed], true))
                    <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="rounded-2xl border border-stone-200 bg-white p-6">
                        @csrf
                        @method('PATCH')
                        <h2 class="text-lg font-semibold">Tindakan moderasi</h2>
                        <div class="mt-5">
                            <label for="status" class="form-label">Status berikutnya</label>
                            <select id="status" name="status" required class="input-field">
                                <option value="">Pilih status</option>
                                @foreach (App\ReportStatus::cases() as $status)
                                    @if ($report->status->canTransitionTo($status))
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('status')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        @if ($report->status === App\ReportStatus::Reviewed)
                            <label class="mt-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm">
                                <input type="checkbox" name="hide_product" value="1" class="mt-0.5 size-4 rounded border-stone-300 text-red-700">
                                <span><strong>Sembunyikan produk</strong><br>Gunakan ketika menyelesaikan laporan yang terbukti. Produk tidak akan tampil publik.</span>
                            </label>
                            @error('hide_product')<p class="form-error">{{ $message }}</p>@enderror
                        @endif
                        <button type="submit" class="button-primary mt-5">Simpan tindakan</button>
                    </form>
                @endif
            </div>

            <article class="rounded-2xl border border-stone-200 bg-white p-6">
                <span class="text-sm font-medium text-stone-500">Produk dilaporkan</span>
                <h2 class="mt-2 text-xl font-semibold">{{ $report->product->name }}</h2>
                <p class="mt-2 text-sm text-stone-600">Seller: {{ $report->product->seller->name }}</p>
                <p class="mt-1 text-sm text-stone-600">Kategori: {{ $report->product->category->name }}</p>
                <p class="mt-4 text-2xl font-semibold">Rp{{ number_format($report->product->price, 0, ',', '.') }}</p>
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-stone-700">{{ $report->product->description }}</p>
                <p class="mt-4 text-sm font-medium">Status: {{ $report->product->status->label() }}{{ $report->product->trashed() ? ' · Dihapus seller' : '' }}</p>
                @if (!$report->product->trashed() && $report->product->status === App\ProductStatus::Available && $report->product->published_at)
                    <a href="{{ route('products.show', $report->product) }}" class="button-secondary mt-5">Lihat halaman publik</a>
                @endif
            </article>
        </div>
    </section>
@endsection
