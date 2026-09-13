# Preloved IN

> **Barang Lama, Kesempatan Baru.**  
> Platform listing dan komunikasi langsung barang preloved. Transaksi barang dilakukan langsung antara buyer dan seller via WhatsApp di luar website. Website **tidak** menyediakan cart, checkout barang, pengiriman, maupun wallet.

---

## 1. Konsep & Aturan Utama

- **Monetisasi Platform**:
  - Biaya listing: Rp3.000 / barang (dapat diatur / free mode saat peluncuran).
  - Biaya boost: Rp3.000 / boost (mendapat prioritas tampil di marketplace dan label `BOOSTED`).
- **Komunikasi**:
  - Pembeli menghubungi penjual secara langsung melalui WhatsApp dengan nomor terverifikasi dan pesan listing URL-safe otomatis.
- **Peran Pengguna**:
  - `buyer`: Default saat pendaftaran. Dapat menjelajah, mencari barang, dan melaporkan produk.
  - `seller`: Diaktifkan secara mandiri oleh buyer. Memiliki seluruh hak buyer ditambah fitur titip barang, kelola foto, mark sold, dan boost.
  - `admin`: Mengelola moderasi produk, laporan pengguna, pengelolaan kategori, pemantauan transaksi pembayaran, dan penanganan akun bermasalah.
- **Admin Moderasi**:
  - Admin **tidak** melakukan persetujuan manual untuk setiap produk baru.
  - Admin menangani: laporan pengguna, spam, penipuan, produk terlarang, kategori, dan akun bermasalah (warning & suspension).

---

## 2. Fitur Utama

1. **Marketplace Publik**:
   - Beranda dengan listing terbaru dan kategori aktif.
   - Halaman Jelajah (`/explore`) dengan pencarian teks, filter kategori, rentang harga, kondisi barang, dan lokasi.
   - Direktori Kategori (`/categories`) & Detail Kategori (`/categories/{slug}`) dengan jumlah listing publik aktif.
   - Halaman Detail Produk (`/products/{slug}`) dengan galeri foto, status boost, dan tombol chat WhatsApp.
   - Profil Seller Publik (`/sellers/{id}`) dengan daftar barang aktif.
   - Dynamic XML Sitemap (`/sitemap.xml`) & `robots.txt`.

2. **Manajemen Seller & Listing**:
   - CRUD barang titipan dengan validasi Form Request.
   - Manajemen 1–5 foto dengan proteksi konkurensi, pemilihan foto utama, dan larangan penghapusan foto terakhir.
   - Penandaan barang terjual (*mark sold*) dan *soft delete* untuk audit.
   - Aktivasi boost berdurasi (gratis / berbayar) dengan batas keadilan 1 boost aktif per seller.
   - Pengaturan nomor WhatsApp mandiri di halaman profil akun (`/profile`).

3. **Sistem Pembayaran Fee (DOKU Checkout)**:
   - DOKU Checkout gateway untuk fee listing dan boost.
   - Verifikasi tanda tangan kriptografis request & response.
   - Notifikasi webhook asinkron dengan verifikasi HMAC-SHA256, deduplikasi, dan idempotensi event.
   - Dukungan full refund & partial refund DOKU.
   - Rekonsiliasi pembayaran terjadwal otomatis via Artisan command (`payments:reconcile`).

4. **Area Administrasi & Moderasi (`/admin`)**:
   - **Dashboard**: Metrik aktual user, listing, laporan pending, dan total pendapatan fee.
   - **Kelola Kategori**: Tambah, edit, aktifkan/nonaktifkan kategori, hapus kategori kosong, dengan pembuatan slug otomatis.
   - **Moderasi Listing**: Cari dan filter seluruh listing, sembunyikan (*hide*) produk terindikasi melanggar, atau aktifkan kembali (*unhide*).
   - **Moderasi Pengguna**: Peringatan bertingkat (*warning count*), penangguhan sementara / permanen (*suspension*), dan pencabutan penangguhan.
   - **Monitoring Pembayaran**: Riwayat invoice, attempt checkout, status fulfillment hak, refund, dan log event webhook.
   - **Pengaturan Sistem**: Pengaturan biaya listing, biaya boost, dan durasi aktif boost (jam).
   - **Audit Trail**: Pencatatan histori moderasi lengkap (`moderation_audits`) untuk kategori, produk, pengaturan, dan user.

---

## 3. Persyaratan Sistem

- PHP 8.3+ (Runtime lokal: PHP 8.5.x)
- Ekstensi PHP: `pdo`, `pdo_sqlite` (atau `pdo_mysql`), `mbstring`, `openssl`, `curl`
- Node.js 20+ & npm
- Composer 2+

---

## 4. Instalasi & Setup Lokal

```bash
# 1. Clone repository & masuk ke direktori
git clone <repo-url>
cd Preloved

# 2. Install dependensi PHP & JavaScript
composer install
npm install

# 3. Salin environment & generate app key
cp .env.example .env
php artisan key:generate

# 4. Jalankan migrasi dan seeding data
php artisan migrate --seed

# 5. Build asset frontend
npm run build

# 6. Jalankan server lokal
php artisan serve
```

Aplikasi dapat diakses melalui browser di `http://localhost:8000`.

---

## 5. Pengujian & Standar Kualitas

```bash
# Menjalankan seluruh test suite (PHPUnit)
php artisan test --compact

# Menjalankan linter & formatter kode (Laravel Pint)
vendor/bin/pint --format agent

# Build aset frontend produksi
npm run build
```

---

## 6. Penjadwalan & Rekonsiliasi Pembayaran

Untuk menjalankan scheduler di server atau lokal:

```bash
php artisan schedule:work
```

Scheduler akan mengeksekusi rekonsiliasi pembayaran setiap 10 menit tanpa tumpang tindih (`withoutOverlapping`):
```bash
php artisan payments:reconcile
```
