# Perbaikan audit web dan mobile — 17 November 2026

Perbaikan berikut dikerjakan di lokal berdasarkan audit web `c1f1f74` dan mobile
`4df52c9`. Pengujian dilakukan di lokal. Status publikasi dan penerapan produksi
mengikuti run CI/CD untuk commit perbaikan pada masing-masing repositori.

## Temuan dan hasil perbaikan

| ID | Perbaikan |
| --- | --- |
| A01 | Memperbarui dependensi yang terdampak advisori. Laravel menjadi 13.42.0 dan PhpSpreadsheet 5.11.0. Menghapus wrapper Excel yang tidak digunakan; ekspor tetap memakai PhpSpreadsheet langsung. Audit dependensi produksi kini tidak menemukan advisori dan menjadi pemeriksaan wajib CI. |
| A02 | Harga, varian, jumlah, dan resep disimpan di server sebelum QRIS ditampilkan. Unggah bukti memakai snapshot tersebut; perubahan harga/resep sesudahnya tidak mengubah nominal pembayaran yang sudah disepakati. |
| A03 | Server menghitung gabungan kebutuhan bahan seluruh keranjang, lalu mereservasi bahan sebelum pelanggan membayar. POS, katalog pemilik, dan mobile menghitung stok tersedia setelah reservasi. Ada penyelesaian bukti tidak valid dan pencatatan pengembalian dana oleh karyawan. |
| A04 | Ekspor pemakaian bahan menghitung takaran per porsi dikali jumlah porsi. Pesanan yang belum diverifikasi tidak dihitung sebagai pemakaian. Ekspor memuat 17 bagian, termasuk sesi pembayaran, reservasi, serta catatan penyelesaiannya. |
| A05 | Logout, pergantian akun, dan token tidak sah membersihkan keranjang tersimpan serta provider yang masih aktif. Identitas akun disimpan agar keranjang tidak terbawa ke akun lain. |
| A06 | Keberhasilan checkout hanya mengurangi jumlah item yang dikirim. Penambahan baru tetap ada. ID keranjang dan daftar checkout yang sudah diterapkan mencegah pengurangan ulang setelah retry atau aplikasi dibuka kembali. Pemulihan pembayaran lama dari server tidak mengurangi keranjang baru. |
| A07 | Polling status diserialisasi dan respons lama tidak boleh menimpa refresh terbaru. Polling berhenti saat aplikasi di latar belakang. Riwayat memakai penjagaan respons yang sama, termasuk refresh dari push; pesanan yang keluar dari antrean memicu refresh riwayat. |
| A08 | Gangguan jaringan/503 saat startup menampilkan pilihan coba lagi dan mempertahankan token. HTTP 401 mengakhiri sesi dan mengarahkan ke login. Respons terlambat dari akun lama tidak boleh mengubah akun baru. Permintaan memiliki batas waktu. |
| A09 | Pemilik tidak dapat menonaktifkan pelanggan yang masih memiliki pesanan aktif, termasuk pesanan siap diambil, atau sesi pembayaran yang masih berlaku. Setelah pesanan selesai diambil/diselesaikan, akun dapat dinonaktifkan. |
| A10 | Rekap pelanggan mobile memakai ID akun. Dua akun dengan nama sama tetap terpisah; transaksi POS berdasarkan nama juga dipisahkan dari akun mobile. |
| N01 | Endpoint gambar publik memblokir folder bukti pembayaran, termasuk variasi path dengan titik/garis miring. Bukti pesanan tetap melalui endpoint yang memeriksa hak akses. |
| N02 | Vendor lokal sudah diselaraskan dengan lockfile. Resolusi dependensi memakai platform PHP 8.3.36 agar sesuai target server. Paket terpasang juga lolos pemeriksaan dan tes pada PHP 8.3.13 serta tidak lagi memiliki batas PHP <8.5 dari spreadsheet lama. |

## Alur QRIS manual

1. Mobile memuat katalog terbaru lalu meminta `POST /api/checkouts` dengan UUID
   `request_key`, daftar item, dan `expected_total`.
2. Server memeriksa akun, produk, harga, resep, serta gabungan kebutuhan bahan.
   Lock transaksi melindungi porsi terakhir ketika POS dan pelanggan lain memesan
   bersamaan. Permintaan ulang dengan kunci yang sama mengembalikan sesi yang sama.
3. Sesi menyimpan harga/resep dan mereservasi bahan selama **15 menit**. Mobile
   menampilkan total dan batas waktunya bersama QRIS. Reservasi kedaluwarsa otomatis
   tidak dihitung dalam stok tersedia; tidak memerlukan cron baru.
4. Menutup lembar QRIS mempertahankan sesi. Tombol pembatalan hanya digunakan jika
   pelanggan belum membayar. Sesi yang dibatalkan atau kedaluwarsa tidak boleh
   menampilkan QR untuk pembayaran baru. Pelanggan yang sudah membayar tetap bisa
   mengirim buktinya.
5. Unggah bukti menyertakan `checkout_id`. Harga dan resep berasal dari snapshot
   server. Reservasi yang masih dapat dipenuhi ditahan sampai karyawan memeriksa
   bukti. Stok fisik baru dikurangi sekali saat verifikasi.
6. Jika bukti terlambat dan stok sudah terpakai, server tetap menyimpan pesanan/bukti
   dengan catatan pemeriksaan. Karyawan dapat memproses setelah stok mencukupi,
   menolak bukti setelah memeriksa mutasi, atau mencatat dana yang sudah dikembalikan.
7. Pencatatan pengembalian **tidak mentransfer uang**. Karyawan wajib mengirim dana
   secara manual terlebih dahulu, mengisi alasan/referensi, dan memberi konfirmasi.
   Petugas, waktu, dan catatan tersimpan. Tindakan berulang atau tindakan oleh
   karyawan lain terhadap pesanan yang sudah ditugaskan ditolak.
8. Pesanan yang ditolak/dikembalikan melepaskan reservasi dan tampil pada riwayat
   pelanggan beserta catatan kasir. Pesanan tersebut tidak dihitung sebagai penjualan.
   Penjualan mobile tetap masuk riwayat transaksi keuangan setelah pelanggan
   menekan **Sudah Ambil**.

Karyawan perlu menyelesaikan pesanan menunggu verifikasi secara rutin: setelah
bukti dikirim, reservasi tidak kedaluwarsa otomatis agar bahan untuk pembayaran
yang sedang diperiksa tidak dijual ke pelanggan lain.

Produk yang sudah tercantum pada sesi pembayaran atau transaksi dipertahankan
untuk integritas riwayat; gunakan status nonaktif jika produk tidak dijual lagi.
Stok bahan tidak dapat diturunkan di bawah jumlah reservasi aktif. Satuan bahan
yang sudah digunakan oleh reservasi tidak boleh diubah.

## Perubahan API dan penerapan berikutnya

Mobile baru mengirim header `X-Ciks-Checkout-Version: 2`. Endpoint katalog dan QRIS
menolak aplikasi lama dengan HTTP 426 sebelum pembayaran. Unggah bukti tanpa sesi
juga ditolak; tidak ada jalur lama yang melewati pemeriksaan harga/stok.

**Web/API, migrasi, dan APK baru harus dirilis secara terkoordinasi.** Selesaikan
sesi QRIS yang sedang terbuka sebelum perpindahan versi agar pelanggan tidak
sedang membayar melalui aplikasi lama. APK baru belum dapat menjalankan alur
checkout baru terhadap API produksi yang belum diperbarui.

Migrasi tambahan:
`database/migrations/2026_11_18_000001_create_mobile_checkouts.php`.
Migrasi ini membuat tabel sesi/reservasi dan menambah kolom penyelesaian pembayaran
serta dua status pesanan. Migrasi lokal sudah dijalankan; hash data akun, produk,
bahan, pesanan, item, dan resep lama dibandingkan sebelum/sesudah dan tetap sama.
Jangan memakai `migrate:fresh` pada data asli.

Workflow pada `main` menjalankan pengujian sebelum publikasi. Penerapan harus
menjalankan migrasi tambahan sebelum mengaktifkan kode baru, memeriksa
layanan/worker khusus Ciks Coffee, lalu mendistribusikan APK dengan nomor build
lebih tinggi dan kunci penandatanganan yang sama. Verifikasi lokal tidak mengubah
VPS; keberhasilan penerapan perlu dilihat pada run CI/CD commit yang dipublikasikan.

## Pengujian

| Pemeriksaan akhir | Hasil |
| --- | --- |
| Web SQLite, PHP 8.3.13 | 171 tes, 1.265 assertion lulus |
| Web MySQL terisolasi, PHP 8.3.13 | 171 tes, 1.265 assertion lulus |
| Transaksi bersamaan MySQL | 7 tes, 49 assertion lulus |
| JavaScript | 38 tes lulus |
| Mobile | 59 tes lulus |
| Validasi arsip deployment / pembungkus APK | 7 / 3 tes lulus |
| Composer produksi | 0 advisori; kebutuhan platform terpenuhi |
| Build | Vite dan APK release ARM64 berhasil |

- PHPUnit seluruh web pada SQLite dan database MySQL sementara yang terisolasi.
- Tujuh tes transaksi bersamaan pada MySQL, termasuk reservasi versus reservasi,
  retry dengan kunci sama, serta POS versus reservasi untuk porsi terakhir.
- Tes regresi harga/resep terkunci, pengembalian/penolakan bukti, pembatasan role,
  reservasi, ekspor, akun aktif, pelanggan bernama sama, dan privasi bukti.
- Tes JavaScript notifikasi, polling adaptif, dan sinkronisasi antrean; build Vite.
- Browser desktop/ponsel untuk stok POS, katalog pemilik, dan editor meja.
  Formulir penyelesaian diuji pada lebar 1440, 768, dan 390 piksel; isian, pilihan,
  checkbox, dan fokus tetap ada ketika pesanan lain berubah secara realtime.
  Perubahan bisnis pada browser dicegat dengan data simulasi.
- Flutter analyze, seluruh tes mobile, serta build APK release ARM64 lokal.
- Audit Composer, pemeriksaan platform, dan tes pembungkus arsip deployment/APK.

Pengujian ini tidak melakukan transfer QRIS/refund bank, mengirim transaksi uji ke
produksi, atau membuktikan pengiriman FCM pada perangkat fisik di semua kondisi
Android. Tidak ada klaim bahwa seluruh kemungkinan bug sudah mustahil terjadi.
