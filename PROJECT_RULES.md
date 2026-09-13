# Preloved IN

## Konsep
Preloved IN adalah platform listing barang preloved.
Website tidak menangani transaksi barang.
Buyer dan seller bertransaksi secara langsung.

## Roles
- buyer
- seller
- admin

## Monetisasi
- Listing: Rp3.000/barang
- Boost: Rp3.000/barang
- Pada fase launch, listing dan boost gratis.

## Produk
Status:
- available
- sold
- hidden

## Boost
Produk boosted mendapat prioritas tampil.
Produk boosted wajib memiliki label "BOOSTED".

## Transaksi
Jangan membuat cart atau checkout barang.
Tidak ada pembayaran barang melalui website.

## Komunikasi
Buyer menghubungi seller secara langsung melalui WhatsApp.

## Admin
Admin tidak melakukan approval setiap produk.
Admin hanya menangani:
- laporan
- spam
- penipuan
- produk terlarang
- user bermasalah

## Coding
- Gunakan Laravel conventions.
- Jangan membuat fitur yang tidak diminta.
- Jangan mengubah database yang sudah ada tanpa alasan.
- Gunakan migrations.
- Gunakan Form Request untuk validasi.
- Gunakan Policies/Middleware untuk authorization.
- Jangan menyimpan password atau data sensitif secara manual.
- Semua halaman harus responsive.