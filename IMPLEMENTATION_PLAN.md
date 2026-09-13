# Preloved IN Implementation Plan

## 1. Kondisi Project Saat Ini

- Laravel 13.31.0 dengan requirement PHP `^8.3`; runtime lokal PHP 8.5.5.
- Database lokal menggunakan SQLite. Session, cache, dan queue menggunakan database.
- Project berawal dari skeleton Laravel dengan satu model `User`, migration infrastruktur Laravel, satu route welcome, dan contoh test bawaan.
- Frontend menggunakan Blade, Tailwind CSS 4, dan Vite 8 tanpa framework JavaScript tambahan.
- Belum ada domain listing, kategori, gambar, boost, payment, report, maupun admin moderation.
- Laravel Boost 2.8.0 dipasang sebelum implementasi untuk menyediakan guideline dan tooling Laravel yang sesuai versi project.
- Scope seluruh operasi adalah folder project ini. Repository Git project berdiri di folder `Preloved`; tidak ada commit yang dilakukan selama implementasi awal.

## 2. Keputusan Arsitektur

### Authentication dan role

- Gunakan authentication berbasis session Laravel dengan flow register, login, dan logout.
- Role tunggal disimpan pada user: `buyer`, `seller`, atau `admin`.
- User baru selalu dibuat sebagai `buyer`; role tidak diterima dari request registrasi.
- Buyer dapat mengaktifkan akun seller. Seller tetap memiliki seluruh kemampuan buyer.
- Admin diprovisi secara terkontrol dan tidak tersedia sebagai pilihan registrasi publik.
- Middleware role digunakan untuk batas route seller dan admin. Ownership resource akan ditangani oleh Policy pada fase terkait.

### Backend

- Gunakan controller yang fokus pada HTTP concern, Form Request untuk validasi, enum PHP untuk nilai status stabil, dan middleware/policy untuk authorization.
- Business logic diekstrak menjadi service hanya ketika memiliki boundary domain atau penggunaan ulang yang nyata.
- Semua state-changing request menggunakan CSRF dan semua output user di Blade menggunakan escaping default.

### Frontend

- Gunakan Blade dan Tailwind CSS 4 untuk MVP server-rendered yang sederhana.
- Layout mobile-first, responsif, accessible, dan tidak bergantung pada JavaScript untuk navigasi inti.
- Gunakan warna netral dengan satu warna aksen; produk menjadi fokus utama pada fase marketplace.

### Batas produk

Aplikasi hanya memfasilitasi listing dan komunikasi langsung. Tidak akan dibuat cart, order barang, checkout barang, pembayaran barang, shipping, wallet, maupun internal chat.

## 3. Database Plan

Tabel domain minimal:

- `users`
- `categories`
- `products`
- `product_images`
- `boosts`
- `payments`
- `reports`
- `settings`

Prinsip schema:

- Harga dan fee disimpan sebagai integer rupiah.
- Foreign key dan index mengikuti pola akses aplikasi.
- Produk menggunakan status `available`, `sold`, atau `hidden` dan soft delete untuk kebutuhan audit.
- `hidden` adalah state moderasi, bukan penghapusan data.
- Foto disimpan pada filesystem; database hanya menyimpan path dan metadata yang dibutuhkan.
- Nomor WhatsApp seller disimpan dalam bentuk ternormalisasi dan tidak diminta berulang pada setiap listing.
- Payment hanya untuk listing dan boost. Metadata provider disimpan sebagai JSON tanpa credential atau data kartu.
- Tabel `settings` menyimpan `listing_fee_enabled`, `boost_fee_enabled`, nominal fee, dan durasi boost setelah durasinya ditentukan.

## 4. Development Phases

### Phase 1 — Foundation

- Authentication register, login, dan logout.
- Role buyer, seller, dan admin; registrasi default buyer.
- Aktivasi seller dan authorization/middleware dasar seller/admin.
- Base layout, navigation berbasis role, dashboard placeholder, dan responsive foundation.
- Feature tests untuk authentication dan batas role.

### Phase 2 — Database Domain

- Migrations, models, relationships, enums, factories, dan seeders untuk categories, products, product images, boosts, payments, reports, dan settings.
- Test schema, casts, relationships, dan factory states.

### Phase 3 — Product Listing

- Seller listing CRUD, upload/hapus gambar, foto utama, authorization ownership, mark sold, dan seller dashboard.
- Listing aktif setelah free-mode atau pembayaran listing berhasil.

### Phase 4 — Public Marketplace

- Homepage, explore, search/filter/sort, category pages, product detail, seller profile, dan pagination.
- Query menggunakan eager loading, index, dan filtering di database.

### Phase 5 — WhatsApp

- Normalisasi nomor seller dan tombol Chat Penjual dengan pesan listing otomatis yang URL-safe.

### Phase 6 — Report

- Form report, pencegahan abuse, admin processing, status report, dan hide listing bermasalah.

### Phase 7 — Boost

- Aktivasi boost, masa aktif configurable, prioritas ranking yang adil, expiry, dan label `BOOSTED` yang jelas.

### Phase 8 — Payment

- Provider-agnostic payment service untuk listing dan boost.
- Server-side webhook verification, idempotency, dan lifecycle pending/paid/failed/expired/refunded.

### Phase 9 — Admin

- Dashboard, user management, category management, moderation produk, report handling, payment monitoring, dan fee/free-mode settings.

### Phase 10 — Polish

- Audit responsive UI, validation, authorization, security, performance, SEO, accessibility, empty/error states, dan regression tests.

## 5. Testing Strategy

- PHPUnit feature tests menjadi default untuk behavior yang dapat diakses melalui HTTP.
- Gunakan SQLite in-memory, `RefreshDatabase`, factories, dan assertion behavior yang spesifik.
- Jalankan test paling sempit setelah perubahan, kemudian full suite sebelum menyatakan satu fase selesai.
- PHP yang berubah diformat menggunakan Laravel Pint; frontend diverifikasi dengan production build.

## 6. Risiko dan Ambiguitas

- Payment provider belum dipilih dan harus diputuskan sebelum Phase 8.
- Durasi boost belum ditentukan dan tidak boleh diasumsikan sebelum Phase 7.
- Batas jumlah/ukuran foto perlu ditentukan sebelum Phase 3.
- Kebijakan visibilitas produk `sold` perlu ditentukan sebelum Phase 4.
- Strategi rotasi boosted listing perlu difinalkan sebelum Phase 7 agar satu seller tidak mendominasi.
- SQLite sesuai untuk development; database production perlu ditentukan sebelum deployment.

## 7. Langkah Pertama

Implementasikan Phase 1 saja: authentication, role, authorization dasar, layout, navigation, dan responsive foundation. Setelah test, formatter, dan build berhasil, berhenti untuk review sebelum memasuki Phase 2.
