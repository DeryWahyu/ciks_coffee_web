# Pengambilan pesanan mobile

Status `selesai` pada pesanan mobile berarti pesanan siap diterima pelanggan.
Pesanan tetap berada di antrean aktif sampai pelanggan menekan **Sudah Ambil**
di aplikasi dan menyetujui dialog konfirmasi. API pickup kemudian mengubah
status menjadi `diambil`.

Web karyawan menerima event perubahan antrean setelah commit dan memperbarui
kartu, jumlah status, serta pagination tanpa memuat ulang halaman. Modal dan
suara notifikasi dapat tetap berjalan selama pembaruan. Lihat
[antrean realtime tanpa reload](order-queue-realtime.md) untuk implementasi dan
hasil pengujiannya.

Riwayat transaksi pemilik/karyawan, detail struk riwayat pemilik, ringkasan,
pendapatan, laporan penjualan, analitik, performa, dan ekspor memakai aturan
yang sama melalui `Order::visibleInTransactionHistory()`. Pesanan POS yang
berhasil dibayar langsung dihitung sebagai transaksi. Riwayat karyawan tetap dibatasi
oleh `cashier_id` karyawan yang masuk.

API pickup hanya menerima pelanggan pemilik pesanan yang sudah `selesai`.
Pengulangan permintaan untuk pesanan `diambil` berhasil tanpa perubahan kedua.
Status dibaca ulang dengan penguncian baris; konfirmasi tidak menagih pembayaran
atau mengurangi bahan baku lagi. Kasir tetap hanya dapat memproses status
`antrian_baru` menjadi `sedang_dibuat`, lalu `selesai`.

Perubahan pickup awal ini tidak membutuhkan migrasi database atau seeder. Deploy backend
untuk mengaktifkan aturan riwayat; APK baru memuat panel keranjang dan dialog
yang diperbarui.

Audit integritas berikutnya pada 25 Oktober 2026 menambahkan empat migrasi
untuk keamanan checkout dan snapshot resep. Lihat
[ringkasan audit transaksi](pos-integrity-audit.md) sebelum menerapkan versi terbaru.

Pengujian alur:

```bash
npm run build
php artisan test --filter=MobilePickupTransactionHistoryTest
```
