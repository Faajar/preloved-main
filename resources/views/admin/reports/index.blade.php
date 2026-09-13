@extends('layouts.app')

@section('title', 'Laporan Produk')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Moderasi</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Laporan Produk</h1>
                <p class="mt-2 text-stone-600">Tinjau laporan pengguna dan ambil tindakan terhadap listing bermasalah.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="button-secondary">Dashboard Admin</a>
        </div>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-8 flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="status" class="form-label">Filter status</label>
                <select id="status" name="status" class="input-field">
                    <option value="">Semua status</option>
                    @foreach (App\ReportStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="button-primary justify-center">Terapkan</button>
            <a href="{{ route('admin.reports.index') }}" class="button-secondary justify-center">Reset</a>
        </form>

        @if ($reports->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <h2 class="text-xl font-semibold">Tidak ada laporan</h2>
                <p class="mt-2 text-stone-600">Laporan sesuai filter akan muncul di sini.</p>
            </div>
        @else
            <div class="mt-8 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="divide-y divide-stone-200">
                    @foreach ($reports as $report)
                        <a href="{{ route('admin.reports.show', $report) }}" class="grid gap-3 p-5 transition hover:bg-stone-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold">#{{ $report->id }} · {{ $report->reason->label() }}</span>
                                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium">{{ $report->status->label() }}</span>
                                </div>
                                <p class="mt-2 truncate text-sm text-stone-700">{{ $report->product->name }}</p>
                                @if ($report->description)
                                    <p class="mt-1 line-clamp-1 text-sm text-stone-500">{{ $report->description }}</p>
                                @endif
                                <p class="mt-1 text-sm text-stone-500">Pelapor: {{ $report->reporter->name }}</p>
                            </div>
                            <time class="text-sm text-stone-500" datetime="{{ $report->created_at->toAtomString() }}">{{ $report->created_at->translatedFormat('d M Y H:i') }}</time>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="mt-8">{{ $reports->links() }}</div>
        @endif
    </section>
@endsection
