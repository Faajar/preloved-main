# Preloved IN — Project Gaps

Dokumen ini merangkum pekerjaan yang masih diperlukan setelah implementasi Phase 1–7 dan fondasi Phase 8 DOKU Checkout.

## 1. Status Project

### Sudah tersedia

- Authentication berbasis session dengan role `buyer`, `seller`, dan `admin`.
- Aktivasi buyer menjadi seller.
- CRUD listing, upload gambar, foto utama, mark sold, dan soft delete.
- Marketplace publik, pencarian, filter, kategori, detail produk, dan profil seller.
- Kontak seller melalui WhatsApp dengan nomor ternormalisasi dan URL-safe message.
- Product reporting, abuse prevention, dan moderasi laporan oleh admin.
- Free boost dengan durasi configurable dan boosted-first ranking.
- Fondasi DOKU Checkout untuk biaya listing dan boost.
- Signature verification untuk notification DOKU.
- Payment event idempotency dan fulfillment dasar listing/boost.
- Riwayat dan detail payment untuk seller.

### Belum selesai

- Phase 8 DOKU belum siap digunakan untuk pembayaran production.
- Sebagian besar Phase 9 Admin belum diimplementasikan.
- Phase 10 polish, security hardening, dan production readiness belum selesai.

## 2. Keputusan Produk dan Operasional

- Payment gateway menggunakan **DOKU Checkout**.
- Metode pembayaran saat launch:
  - Virtual Account.
  - QRIS.
  - E-wallet.
- Kartu kredit dan paylater belum diaktifkan pada tahap awal.
- Payment hanya untuk biaya listing dan boost.
- Harga barang tidak dibayar melalui platform.
- Tidak ada cart, order barang, shipping, wallet, escrow, atau internal chat.
- Refund dilakukan manual oleh admin melalui prosedur DOKU.
- Notification refund yang terverifikasi membatalkan entitlement terkait.
- Moderasi user menggunakan peringatan bertingkat sebelum suspension.
- Target deployment production belum ditentukan sehingga arsitektur harus portable.

## 3. DOKU Payment Hardening — Prioritas Kritis

Fee listing dan boost harus tetap nonaktif untuk transaksi nyata sampai bagian ini selesai.

### 3.1 Kompatibilitas refund DOKU — selesai 10 September 2026

Implementasi telah mendukung:

- Request ID refund boleh berbeda dari checkout request ID.
- Nominal dari `refund.amount`, dengan fallback `order.amount` untuk payload channel lama.
- Provider refund ID/reference, jumlah, alasan, waktu, dan provider status.
- Full refund dan partial refund, termasuk akumulasi partial refund sampai penuh.
- Full refund membatalkan entitlement.
- Partial refund masuk review admin dan tidak otomatis membatalkan entitlement.
- Duplicate refund notification idempotent, termasuk validasi konflik reference/amount.
- Test payload refund DOKU representatif, termasuk contoh notification kartu resmi.

### 3.2 Verifikasi response DOKU — selesai 10 September 2026

Implementasi telah mendukung:

- Verifikasi response signature DOKU dari raw response body.
- Verifikasi `Client-Id` dan `Request-Id`.
- Pencocokan invoice number, amount, dan currency.
- Validasi success response semantics.
- Session ID dan token reference tetap optional.
- Checkout URL wajib HTTPS dan host-nya berada pada allowlist DOKU.

### 3.3 Recovery timeout dan HTTP 409 — sebagian besar selesai 10 September 2026

Implementasi telah mendukung:

- Menyimpan exact canonical request payload sebelum request pertama.
- Menyimpan request ID dan payload hash.
- Retry dengan request ID dan body yang identik.
- Menangani DOKU `409 Conflict` sebagai kemungkinan idempotent replay.
- Tidak langsung membuat invoice baru ketika hasil request sebelumnya ambigu.
- Menyimpan attempt count dan last provider error.

Masih diperlukan:

- Test eksplisit connection timeout setelah provider menerima request.

### 3.4 DOKU Check Status — fondasi selesai 10 September 2026

Sudah tersedia:

- Status inquiry pada payment gateway contract.
- Reconciliation untuk hasil create-checkout ambigu, HTTP 409, dan provider `TIMEOUT`.
- `TIMEOUT` tetap non-final dan payment tetap pending.
- Verifikasi signature dan identitas response Check Status.

Masih diperlukan:

- Scheduled reconciliation untuk notification yang hilang dan payment pending terlalu lama.
- Deteksi perbedaan status local/provider secara terjadwal.
- Manual refresh oleh admin.

### 3.5 Fulfillment recovery

Pisahkan payment lifecycle dan fulfillment lifecycle. Fulfillment disarankan memiliki status:

- `pending`
- `processing`
- `fulfilled`
- `failed`
- `reversed`

Tambahkan:

- Queued fulfillment yang idempotent.
- Retry count dan next retry time.
- Last failure detail.
- Admin retry fulfillment.
- Admin mark-for-refund.
- Alert untuk payment `paid` tetapi belum fulfilled.
- Jaminan entitlement hanya dibuat satu kali.

### 3.6 Payment attempt replacement — selesai 10 September 2026

Seller sekarang dapat membuat attempt baru untuk payment expired atau failed:

- Histori attempt lama tetap disimpan.
- Attempt baru menggunakan sequence, invoice, dan request ID baru.
- Hanya current attempt yang efektif untuk checkout.
- Listing yang payment-nya expired tidak lagi tertahan tanpa tindakan.
- Pending paid-boost tetap menjaga batas satu boost efektif per seller.

### 3.7 Asynchronous webhook inbox

Notification endpoint sebaiknya menjalankan flow:

1. Batasi ukuran request.
2. Validasi panjang header, termasuk maksimum Request ID.
3. Verifikasi `Client-Id`, timestamp, digest, dan signature.
4. Simpan event dan payload hash secara durable.
5. Return acknowledgement secepat mungkin.
6. Proses state transition dan fulfillment melalui queued job.
7. Retry processing secara idempotent.
8. Simpan `processing_error` jika gagal.

Event invalid, rejected, unhandled, dan failed-processing tetap perlu audit record yang aman. Payload tidak boleh menyimpan credential atau data sensitif yang tidak dibutuhkan.

### 3.8 Unknown status handling

- Jangan menandai status DOKU yang belum dikenal sebagai successfully processed.
- Simpan status sebagai `unhandled`.
- Buat alert operasional.
- Sediakan mekanisme reprocess setelah mapper diperbarui.
- Lindungi payment final agar tidak downgrade akibat stale notification.

### 3.9 Public URL dan environment safeguards

Tambahkan konfigurasi eksplisit untuk:

- Public payment origin.
- Callback URL.
- Notification URL.
- Allowed DOKU hosts.
- Sandbox atau production mode.

Production harus gagal tertutup jika:

- Public URL masih localhost.
- Callback/notification bukan HTTPS.
- DOKU base URL masih sandbox.
- Credential kosong.
- Queue atau scheduler yang dibutuhkan belum siap.

Trusted hosts dan trusted proxies harus dikonfigurasi sesuai platform deployment.

### 3.10 Local expiration dan reconciliation schedule

- Scheduled task untuk payment pending melewati `expires_at`.
- Reconcile sebelum menetapkan status final pada outcome yang ambigu.
- Scheduled scan untuk paid-but-unfulfilled.
- Scheduled scan untuk event unhandled atau processing error.
- Hindari payment tertahan pending tanpa batas waktu.

## 4. Admin Payment Operations

Admin membutuhkan payment operation center yang mencakup:

### Dashboard payment

- Pending payments.
- Paid payments.
- Expired dan failed payments.
- Refunded payments.
- Paid-but-unfulfilled payments.
- Unhandled webhook events.
- Processing errors.
- Pendapatan listing fee.
- Pendapatan boost fee.

### Daftar dan detail payment

Filter dan pencarian berdasarkan:

- Invoice.
- Provider reference.
- Seller.
- Product.
- Payment type.
- Payment status.
- Fulfillment status.
- Tanggal.

Detail payment perlu menampilkan:

- Immutable amount dan duration snapshot.
- Lifecycle timestamps.
- DOKU references yang aman.
- Checkout attempts.
- Webhook event history.
- Fulfillment history.
- Refund history.
- Internal notes.

Credential dan signature lengkap tidak boleh ditampilkan.

### Admin actions

- Refresh status dari DOKU.
- Retry fulfillment.
- Reprocess webhook event.
- Tandai perlu refund.
- Catat dan verifikasi refund dari DOKU Back Office.
- Resolve processing error.
- Tambahkan internal note.

Semua action harus tercatat pada audit trail.

## 5. Sisa Phase 9 Admin

### 5.1 Dashboard metrics

Ganti placeholder dengan metrik aktual:

- Total buyer dan seller.
- Seller aktif.
- User warned atau suspended.
- Listing active, sold, hidden, dan deleted.
- Pending reports.
- Payment issues.
- Pendapatan listing dan boost.

### 5.2 User moderation bertingkat

Lifecycle yang disarankan:

1. Active.
2. Warned.
3. Temporarily suspended.
4. Permanently suspended.

Data yang perlu disimpan:

- Warning count.
- Warning reason.
- Suspension status.
- Suspension expiry.
- Moderator/admin actor.
- Internal notes.
- Created and resolved timestamps.

Aturan:

- Warning tidak otomatis menghapus listing.
- Suspension memblokir aksi seller, report submission, dan payment initiation.
- Temporary suspension berakhir otomatis atau dicabut admin.
- Permanent suspension hanya dapat dicabut admin.
- Listing dapat di-hide melalui action moderasi terpisah.
- Histori report dan payment tidak dihapus.

### 5.3 Category management

- Create dan edit category.
- Activate/deactivate category.
- Unique slug validation.
- Product count.
- Cegah penghapusan category yang masih digunakan.
- Deactivation menghilangkan listing terkait dari publik tanpa menghapus data.
- Audit setiap perubahan.

### 5.4 Product moderation

- Search dan filter seluruh listing.
- Tampilkan active, sold, hidden, dan soft-deleted listing.
- Hide dan unhide.
- Moderation reason dan internal note.
- Seller history dan related reports.
- Action history.
- Tidak membuat approval manual untuk setiap listing baru.

### 5.5 Settings management

Kelola:

- `listing_fee_enabled`
- `listing_fee`
- `boost_fee_enabled`
- `boost_fee`
- `boost_duration_hours`

Safeguards:

- Nominal dan durasi harus positif.
- Paid mode tidak dapat diaktifkan jika DOKU belum siap.
- Setiap perubahan setting harus diaudit.
- Payment lama tetap menggunakan amount dan duration snapshot lama.

### 5.6 Moderation audit trail

Catat actor, action, subject, nilai sebelum/sesudah, reason, dan timestamp untuk:

- Report transitions.
- User warning/suspension.
- Product hide/unhide.
- Category changes.
- Settings changes.
- Payment intervention.
- Refund decisions.

## 6. Account Lifecycle dan Trust

### Authentication dan account management

- Forgot password.
- Reset password.
- Email verification.
- Resend verification dengan throttle.
- Edit nama dan email.
- Change password dengan current-password confirmation.
- Update WhatsApp secara mandiri.
- Account deactivation/deletion policy.
- Require verified email untuk seller activation, report, dan payment.

### Anti-abuse

- Registration throttling.
- Listing creation/upload throttling.
- Payment initiation throttling.
- Risk-based CAPTCHA jika abuse aktual muncul.
- Suspension middleware.
- Login lockout regression test.

### Seller trust indicators

- Join date.
- Verified contact status.
- Listing count.
- Seller status.
- Safety guidance dan anti-fraud notice.
- Optional seller profile description/location.

## 7. Image Processing dan Storage

### Upload processing

- Maximum pixel dimensions.
- Decode dan re-encode file.
- Strip EXIF/GPS metadata.
- Normalize orientation.
- Generate thumbnail untuk card.
- Generate medium/detail variants.
- WebP/AVIF jika didukung.
- Malware/content scanning sesuai tingkat risiko.

### Image data integrity

- Jangan izinkan penghapusan gambar terakhir dari listing aktif.
- Lock product selama mutasi image.
- Pastikan maksimum lima image aman terhadap concurrent request.
- Pastikan hanya satu primary image.
- Pastikan sort order unik dan konsisten.
- Hapus physical file setelah database commit.
- Retry cleanup jika storage deletion gagal.

### Storage lifecycle

- Gunakan application-specific configurable disk.
- Object storage-ready.
- Public derivatives untuk marketplace.
- Private storage untuk moderation evidence.
- Retention dan purge policy.
- CDN/cache headers.

## 8. Production Readiness

Target deployment belum dipilih. Baseline harus tetap portable.

### Infrastructure

- PostgreSQL atau MySQL untuk production.
- Redis atau managed equivalent untuk cache/queue.
- S3-compatible object storage.
- Queue worker.
- Scheduler process.
- HTTPS.
- Trusted proxies dan hosts.
- Persistent application logs atau external log collector.

### Environment production

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- Correct HTTPS `APP_URL`.
- Correct Indonesian locale/timezone display policy.
- Secure session cookies.
- Production mail provider.
- Production DOKU endpoint dan credentials.
- Secrets disimpan di environment/secret manager, bukan repository.

### CI/CD

Pipeline minimal menjalankan:

1. Composer install.
2. npm install.
3. PHPUnit.
4. Laravel Pint.
5. Vite production build.
6. Composer audit.
7. npm audit.
8. Migration smoke test.

Deployment flow perlu mencakup:

- Maintenance strategy.
- Database migration.
- Config/route/view cache.
- Queue restart.
- Health/readiness check.
- Rollback procedure.

### Backup dan restore

Backup mencakup:

- Database.
- Product images.
- Private moderation evidence.

Definisikan:

- Frequency.
- Retention.
- Encryption.
- Off-site storage.
- Backup failure alert.
- Periodic restore test.

### Monitoring dan alerting

- Centralized logs/error tracker.
- External uptime check.
- Queue failure monitoring.
- Database/storage capacity monitoring.
- Webhook 4xx/5xx alerts.
- Invalid signature spike alert.
- Pending-past-expiry alert.
- Paid-but-unfulfilled alert.
- Local/provider mismatch alert.
- Backup success/failure alert.

## 9. Security Hardening

- Enforce HTTPS.
- Secure session cookies.
- HSTS.
- Content Security Policy.
- `X-Content-Type-Options`.
- Frame protection.
- Referrer Policy.
- Permissions Policy.
- Trusted hosts/proxies.
- Production debug disabled.
- Secret rotation procedure.
- Sensitive log redaction.
- Webhook body-size limit.
- Header and Request ID length validation.
- Controlled malformed JSON/date handling.
- Dependency audit in CI.
- Database constraints untuk valid status/type/currency/amount jika didukung.
- Hilangkan predictable seeded account dari production seeding.

## 10. UX dan Mobile

- Mobile logout.
- Role-aware mobile account/dashboard link.
- Payment navigation untuk seller.
- Confirmation untuk destructive actions.
- Double-submit protection.
- Restore soft-deleted listing.
- Relist atau duplicate sold listing.
- Payment expiry, paid, refunded, dan fulfillment status yang jelas.
- Refresh/reconciliation affordance setelah kembali dari DOKU.
- Custom error pages untuk 403, 404, 419, 429, 500, dan maintenance.
- Perbarui copy dashboard yang masih menyebut fitur seller sebagai fase berikutnya.

## 11. Accessibility

- Skip-to-content link.
- Focusable main content target.
- `aria-current` pada active navigation.
- `aria-invalid` pada invalid fields.
- `aria-describedby` untuk validation errors.
- Error summary dan focus management.
- Accessible payment/status badges.
- Confirmation untuk tindakan moderasi berisiko.
- Width/height pada image untuk mengurangi layout shift.
- Keyboard navigation audit.
- Color contrast audit.
- Automated accessibility checks jika tool telah dipilih.

## 12. SEO

- XML sitemap untuk public listings/categories/sellers.
- Product/Offer structured data.
- Breadcrumb structured data.
- Twitter card metadata.
- `og:type` dan site metadata.
- `noindex` untuk login, register, dashboard, seller management, admin, dan payment pages.
- Block crawler di staging/non-production.
- Pagination canonical policy.
- Dynamic sitemap hanya berisi public listings.
- Favicon/application icons dan web manifest yang sesuai brand.

## 13. Performance dan Scalability

- Structured province/city location data.
- Equality filter untuk lokasi.
- Full-text search atau search service untuk nama/deskripsi.
- Query-count/N+1 regression tests.
- Production-scale query benchmark.
- Cache category dan settings yang relatif stabil.
- Responsive thumbnails dan `srcset`.
- CDN/object storage cache headers.
- Benchmark boost ranking query pada data besar.
- Review dashboard aggregate query.

## 14. Testing yang Masih Diperlukan

### Payment

- DOKU response signature.
- 409 idempotent replay.
- Connection loss setelah checkout dibuat.
- Concurrent checkout initiation.
- Check Status reconciliation.
- [Selesai 10 September 2026] DOKU refund payload representatif, termasuk payload kartu resmi dan field `refund.*`.
- [Selesai 10 September 2026] Partial refund dan akumulasi hingga full refund.
- [Selesai 10 September 2026] Refund request ID berbeda dari checkout request ID.
- `TIMEOUT` non-final behavior.
- Out-of-order notification.
- Unknown status handling.
- Failed fulfillment retry.
- Concurrent refund reversal.
- [Selesai 10 September 2026] Expired dan failed payment attempt replacement.
- Malformed dan oversized webhook input.
- Production HTTPS/public URL validation.

### Admin dan account

- User warning/suspension boundaries.
- Admin category/product/settings authorization.
- Payment remediation authorization.
- Forgot/reset password.
- Email verification.
- Profile/password/WhatsApp update.
- Registration throttling.
- Login lockout.

### Quality

- Security header tests.
- Sitemap/structured-data/noindex tests.
- Accessibility tests.
- Query-count/performance regression tests.
- Production database compatibility test.
- Deployment smoke test.
- Backup/restore verification.
- Hapus atau ganti scaffold unit test yang tidak menguji behavior project.

## 15. Documentation dan Git

### Documentation

README project perlu menggantikan boilerplate Laravel dan menjelaskan:

- Tujuan dan batas platform.
- Local setup.
- Database dan storage.
- Queue dan scheduler.
- DOKU sandbox configuration.
- Notification URL.
- Payment simulation.
- Free dan paid mode.
- Deployment.
- Backup dan restore.
- Troubleshooting.

### Git baseline

Repository belum memiliki baseline commit. Sebelum perubahan besar berikutnya:

1. Audit secrets.
2. Pastikan `.env` tidak tracked.
3. Jalankan full test, formatter, build, dan audits.
4. Buat initial baseline commit jika diminta.
5. Gunakan commit terpisah per fase.

## 16. Urutan Implementasi yang Direkomendasikan

1. [Selesai 10 September 2026] Perbaiki refund compatibility DOKU.
2. [Selesai 10 September 2026] Tambahkan DOKU response signature dan identity validation.
3. [Fondasi selesai 10 September 2026] Tambahkan Check Status, timeout recovery, dan 409 replay handling; sisa scheduled reconciliation dan test connection-loss eksplisit.
4. Tambahkan fulfillment retry. [Payment attempt replacement selesai 10 September 2026.]
5. Ubah webhook menjadi durable asynchronous inbox.
6. Bangun admin payment monitoring dan remediation.
7. Bangun user warning/suspension dan moderation audit trail.
8. Bangun admin category, product, dashboard, dan settings.
9. Lengkapi account lifecycle dan anti-abuse.
10. Perbaiki image processing, integrity, dan storage.
11. Siapkan production database, object storage, queue, dan scheduler.
12. Tambahkan CI/CD, backup, restore, monitoring, dan alerting.
13. Tambahkan security headers dan production safeguards.
14. Selesaikan mobile UX, accessibility, SEO, dan performance.
15. Jalankan DOKU sandbox acceptance untuk setiap metode pembayaran yang diaktifkan.
16. Lakukan production readiness review sebelum fee diaktifkan.

## 17. Release Gate

`listing_fee_enabled` dan `boost_fee_enabled` harus tetap nonaktif untuk transaksi nyata sampai minimal kondisi berikut terpenuhi:

- Refund DOKU telah kompatibel dengan channel launch.
- Response signature DOKU diverifikasi.
- Check Status dan reconciliation tersedia.
- Timeout/409 recovery tersedia.
- Expired attempt dapat diganti.
- Paid-but-unfulfilled dapat dipulihkan atau direfund.
- Admin payment monitoring tersedia.
- Callback dan notification menggunakan public HTTPS URL.
- Queue, scheduler, monitoring, dan backup production siap.
- Sandbox acceptance test VA, QRIS, dan e-wallet berhasil.
- Full test suite, formatter, production build, dan dependency audits berhasil.
