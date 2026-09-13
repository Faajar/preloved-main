@extends('layouts.app')

@section('title', 'Detail User '.$user->name)

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke daftar pengguna</a>

        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <span class="eyebrow">Moderasi Akun Pengguna</span>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ $user->name }}</h1>
                <p class="mt-2 text-stone-600">
                    Role: <strong>{{ $user->role->label() }}</strong> · 
                    Status: 
                    @if ($user->isSuspended())
                        <span class="font-semibold text-red-600">Ditangguhkan (hingga {{ $user->isPermanentlySuspended() ? 'Permanen' : $user->suspended_until->translatedFormat('d M Y H:i') }})</span>
                    @elseif ($user->warning_count > 0)
                        <span class="font-semibold text-amber-700">{{ $user->warning_count }} Peringatan</span>
                    @else
                        <span class="font-semibold text-emerald-700">Aktif Normal</span>
                    @endif
                </p>
            </div>
            <time class="text-sm text-stone-500" datetime="{{ $user->created_at->toAtomString() }}">Terdaftar: {{ $user->created_at->translatedFormat('d M Y H:i') }}</time>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Informasi Akun</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-stone-500">Nama</dt><dd class="font-medium text-stone-900">{{ $user->name }}</dd></div>
                        <div><dt class="text-stone-500">Email</dt><dd class="font-medium text-stone-900">{{ $user->email }}</dd></div>
                        <div><dt class="text-stone-500">Nomor WhatsApp</dt><dd class="font-medium text-stone-900">{{ $user->whatsapp_number ? '+'.$user->whatsapp_number : 'Belum diisi' }}</dd></div>
                        <div><dt class="text-stone-500">Jumlah Peringatan</dt><dd class="font-bold text-amber-800">{{ $user->warning_count }} kali</dd></div>
                    </dl>

                    @if ($user->isSuspended() && $user->suspension_reason)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                            <strong>Alasan Penangguhan:</strong> {{ $user->suspension_reason }}
                        </div>
                    @endif
                </article>

                @unless ($user->isAdmin())
                    <article class="rounded-2xl border border-stone-200 bg-white p-6">
                        <h2 class="text-lg font-semibold">Tindakan Moderasi User</h2>

                        <!-- Beri Peringatan -->
                        <form method="POST" action="{{ route('admin.users.warn', $user) }}" class="mt-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                            @csrf
                            <h3 class="font-semibold text-amber-900">Kirim Peringatan Bertingkat</h3>
                            <p class="mt-1 text-xs text-amber-800">Menambah catatan peringatan pada profil user tanpa langsung memblokir akses.</p>
                            <label for="warn_reason" class="form-label mt-3">Alasan peringatan</label>
                            <textarea id="warn_reason" name="reason" rows="2" maxlength="1000" required class="input-field" placeholder="Contoh: Terdeteksi mengirim pesan spam pada listing">{{ old('reason') }}</textarea>
                            <button type="submit" class="button-secondary mt-3 text-xs">Catat Peringatan</button>
                        </form>

                        @if ($user->isSuspended())
                            <!-- Cabut Penangguhan -->
                            <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                                @csrf
                                <h3 class="font-semibold text-emerald-950">Cabut Penangguhan Akun (Unsuspend)</h3>
                                <p class="mt-1 text-xs text-emerald-800">Mengembalikan hak akses akun user sehingga dapat beraktivitas kembali.</p>
                                <label for="unsuspend_reason" class="form-label mt-3">Alasan pencabutan</label>
                                <textarea id="unsuspend_reason" name="reason" rows="2" maxlength="1000" required class="input-field" placeholder="Contoh: Klarifikasi diterima dan masa hukuman berakhir">{{ old('reason') }}</textarea>
                                <button type="submit" class="button-primary mt-3 text-xs">Cabut Penangguhan</button>
                            </form>
                        @else
                            <!-- Tangguhkan Akun -->
                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="mt-4 rounded-xl border border-red-200 bg-red-50/50 p-4">
                                @csrf
                                <h3 class="font-semibold text-red-950">Tangguhkan Akun (Suspend)</h3>
                                <p class="mt-1 text-xs text-red-800">Memblokir akses menitipkan barang, pembayaran fee, dan pelaporan.</p>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="suspend_type" class="form-label">Tipe Penangguhan</label>
                                        <select id="suspend_type" name="type" required class="input-field">
                                            <option value="temporary">Sementara (Hari)</option>
                                            <option value="permanent">Permanen</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="suspend_days" class="form-label">Durasi Hari (jika sementara)</label>
                                        <input id="suspend_days" name="days" type="number" min="1" max="365" value="7" class="input-field">
                                    </div>
                                </div>

                                <label for="suspend_reason" class="form-label mt-3">Alasan Penangguhan</label>
                                <textarea id="suspend_reason" name="reason" rows="2" maxlength="1000" required class="input-field" placeholder="Contoh: Terbukti melakukan percobaan penipuan WhatsApp">{{ old('reason') }}</textarea>
                                <button type="submit" class="mt-3 inline-flex items-center justify-center rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800">Tangguhkan Akun</button>
                            </form>
                        @endif
                    </article>
                @endunless

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Barang Titipan Terakhir ({{ $user->products_count }})</h2>
                    @if ($user->products->isEmpty())
                        <p class="mt-3 text-sm text-stone-500">User ini belum menitipkan barang.</p>
                    @else
                        <div class="mt-3 divide-y divide-stone-100 text-sm">
                            @foreach ($user->products as $prod)
                                <div class="py-2.5 flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-stone-900">{{ $prod->name }}</span>
                                        <span class="ml-2 text-xs text-stone-500">Rp{{ number_format($prod->price, 0, ',', '.') }}</span>
                                    </div>
                                    <a href="{{ route('admin.products.show', $prod->id) }}" class="text-xs font-medium text-emerald-700">Moderasi →</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>

            <div class="grid content-start gap-6">
                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Histori Moderasi Akun Ini</h2>
                    @if ($user->moderationSubjectAudits->isEmpty())
                        <p class="mt-3 text-sm text-stone-500">Belum ada tindakan moderasi pada user ini.</p>
                    @else
                        <div class="mt-4 grid gap-3 text-sm">
                            @foreach ($user->moderationSubjectAudits as $aud)
                                <div class="border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-stone-900">{{ $aud->action }}</span>
                                        <span class="text-xs text-stone-400">{{ $aud->created_at->translatedFormat('d M Y H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-stone-500 mt-0.5">Oleh: {{ $aud->actor->name }}</p>
                                    @if ($aud->reason)
                                        <p class="mt-1.5 rounded-lg bg-stone-50 p-2 text-xs text-stone-700">{{ $aud->reason }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>

                <article class="rounded-2xl border border-stone-200 bg-white p-6">
                    <h2 class="text-lg font-semibold">Laporan Diajukan ({{ $user->reports_count }})</h2>
                    @if ($user->reports->isEmpty())
                        <p class="mt-3 text-sm text-stone-500">User ini belum pernah melaporkan produk.</p>
                    @else
                        <div class="mt-3 divide-y divide-stone-100 text-sm">
                            @foreach ($user->reports as $rep)
                                <div class="py-2 flex items-center justify-between">
                                    <span>#{{ $rep->id }} · {{ $rep->reason->label() }}</span>
                                    <a href="{{ route('admin.reports.show', $rep) }}" class="text-xs text-emerald-700 font-medium">Buka →</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </div>
    </section>
@endsection
