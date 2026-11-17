# Ekspor data pemilik

Menu **Ekspor Data** menyediakan dua cakupan. **Seluruh data penting** adalah
pilihan awal; **Laporan penjualan saja** mempertahankan laporan delapan kolom
sebelumnya. URL ekspor menerima `scope=all` (default) atau `scope=sales`.

## Isi ekspor lengkap

Ada 17 bagian: ringkasan, transaksi, seluruh pesanan, item pesanan, pemakaian
bahan per item, sesi pembayaran, reservasi bahan, produk beserta harga dan stok per varian, kategori, bahan baku,
resep per varian, pelanggan/staf, denah beserta objek JSON, meja termasuk yang
diarsipkan, riwayat status meja, pengaturan toko, dan daftar berkas.

ID dipertahankan agar hubungan antarbagian tetap jelas. Nama produk pada item
pesanan adalah nama saat transaksi; resep produk dan stok adalah kondisi saat
ekspor. Posisi dan ukuran meja menggunakan persentase kanvas, sama dengan editor.
Kebutuhan resep dan pemakaian bahan menggunakan satuan bahan masing-masing.
Pemakaian aktual menghitung takaran per porsi dikali jumlah porsi dan hanya
memuat pesanan yang stoknya sudah dikurangi; bukti menunggu verifikasi belum
menjadi pemakaian. Reservasi dicatat terpisah dari pengurangan stok fisik.
Stok produk yang berbagi bahan **tidak boleh dijumlahkan**.

Transaksi dan omzet mengikuti `visibleInTransactionHistory()`, sama dengan
laporan web: pesanan mobile yang belum dikonfirmasi diambil tidak menambah
omzet, tetapi tetap tercatat pada bagian seluruh pesanan.

Filter tanggal/metode pembayaran berlaku pada pesanan dan turunannya. Filter
tanggal juga berlaku pada riwayat status meja. Master data selalu lengkap,
termasuk akun, produk, kategori dan meja nonaktif. Rentang tanggal inklusif.

- **CSV lengkap**: ZIP dengan 17 CSV UTF-8, `manifest.json`, `BACA_SAYA.txt`,
  dan gambar produk, bukti pembayaran, QRIS, serta latar denah yang tersedia.
  Lampiran dibaca hanya dari direktori bisnis yang diizinkan. Berkas hilang
  atau referensi tidak valid ditandai dalam manifest dan daftar berkas.
- **Excel lengkap**: XLSX dengan 17 lembar; kolom nominal bertipe angka.
- **PDF lengkap**: bagian per dataset dan rincian per catatan agar kolom yang
  banyak tetap terbaca. Excel/PDF memuat referensi gambar, bukan lampirannya.

Kolom teks dinetralkan terhadap formula spreadsheet. Akun tidak menyertakan
password, token, sesi login, atau kunci API. Pengaturan toko memakai daftar kolom/kunci
yang diizinkan, bukan salinan semua konfigurasi. Arsip ini adalah ekspor data
bisnis, **bukan backup pemulihan database/server**.

CSV membaca data per kelompok kecil. Excel/PDF mempunyai batas baris pada
`config/exports.php` untuk menjaga memori; jika melebihi batas, permintaan
ditolak dengan petunjuk menggunakan CSV. Tidak ada pemotongan data diam-diam.

## Pemakaian dan penyimpanan

Ekspor dijalankan melalui tombol unduh di menu pemilik. Hasil sementara berada
pada disk privat `local`, di bawah `exports/`, dan dihapus setelah respons
unduhan selesai dikirim. Semua endpoint ekspor memerlukan login pemilik aktif.

```powershell
php artisan test --filter="FullSystemExportTest|PemilikExportFormulaInjectionTest|MobilePickupTransactionHistoryTest|PosConsistencyAuditTest"
npm run build
```

Pengujian menggunakan database terisolasi yang dijaga oleh `tests/TestCase.php`
dan storage palsu. Arsip diuji untuk kelengkapan hubungan data, aturan pickup,
filter, pembacaan beberapa kelompok data, tipe sel Excel, penanganan berkas
hilang, formula spreadsheet dan hak akses.
