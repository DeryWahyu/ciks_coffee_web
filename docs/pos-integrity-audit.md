# Ringkasan audit transaksi dan sinkronisasi CiksCoffee

Tanggal: 25 Oktober 2026. Status: perbaikan tersedia di working tree lokal,
belum di-commit, di-push, atau di-deploy pada pekerjaan ini.

## Kesimpulan

Ditemukan masalah yang dapat menyebabkan transaksi berulang, stok bersama
terhitung keliru, pembayaran ditolak akibat pecahan desimal, dan perbedaan
angka antara riwayat transaksi dengan laporan. Masalah yang berhasil
direproduksi sudah diperbaiki dan diuji. Hasil pengujian tidak berarti seluruh
kemungkinan bug atau keadaan data produksi sudah terjamin bebas masalah.

### Aturan yang digunakan

- Karyawan menjalankan POS dan memproses pesanan. POS menerima cash dan QRIS.
- Pemilik memantau transaksi dan laporan; akses checkout POS tetap ditolak.
  Menu pengelolaan yang sebelumnya tersedia tetap mengikuti otorisasi sistem.
- Mobile hanya menerima QRIS dengan unggahan bukti pembayaran. Permintaan
  cash yang dikirim langsung ke API juga ditolak.
- Kasir memverifikasi bukti QRIS sebelum pesanan masuk antrean pembuatan.
- Mobile berstatus `selesai` berarti siap diambil. Riwayat dan laporan baru
  memasukkannya setelah pelanggan menekan **Sudah Ambil** (`diambil`).
- POS yang berhasil dibayar masuk riwayat dan pendapatan langsung, termasuk
  ketika pembuatannya masih berjalan. Jumlah pesanan selesai adalah metrik
  operasional tersendiri.
- Periode laporan tetap menggunakan `created_at`, sesuai kebijakan sebelumnya.

## Temuan dan perbaikan

| Area | Masalah atau risiko yang diperiksa | Hasil perbaikan |
| --- | --- | --- |
| Checkout POS | Klik berulang atau respons koneksi hilang dapat membuat transaksi baru | Kunci pembayaran unik, pemeriksaan isi permintaan, dan pemulihan permintaan yang sama setelah halaman dimuat ulang |
| Checkout mobile | Mengulang unggahan bukti dapat membuat pesanan ganda | Hash bukti per pelanggan mengembalikan pesanan asli untuk keranjang identik; penggunaan bukti yang sama untuk keranjang lain ditolak |
| Stok | Dua produk menggunakan bahan yang sama, atau dua kasir membeli porsi terakhir | Kebutuhan seluruh keranjang dijumlahkan; bahan dikunci dan dikurangi dalam satu transaksi database |
| Nominal dan takaran | Perhitungan float seperti 0,10 + 0,20 dapat menolak uang atau stok 0,30 | Perhitungan menggunakan satuan bulat perseratus; batas nominal dan jumlah divalidasi |
| Harga | Harga katalog berubah sesudah kasir membuka pembayaran | Total yang dikonfirmasi dibandingkan dengan harga server; kasir diminta memeriksa keranjang kembali |
| Resep QRIS | Mengubah resep sesudah pelanggan memesan dapat mengurangi bahan yang berbeda | Resep disimpan bersama item pesanan dan dipakai pada verifikasi |
| Nomor pesanan | Transaksi pertama yang bersamaan dan urutan setelah 9999 | Counter harian dengan penguncian; nomor lama dipakai sebagai awal counter tanpa penomoran ulang |
| Produk dan kategori | Kategori nonaktif masih bisa dibeli; mengganti nama Coffee mengubah pengenalan Base/Lite | Kategori nonaktif ditolak pada katalog dan checkout; slug kategori tetap saat nama tampilannya diganti |
| Varian dan resep | Base habis menyembunyikan Lite yang masih tersedia; resep kosong atau bahan berulang | Produk tersedia bila ada varian yang dapat dibuat; varian tetap divalidasi; produk aktif wajib memiliki resep dan bahan unik per varian |
| Edit stok | Formulir lama dapat menimpa stok yang baru dikurangi POS | Formulir mengirim stok saat dibuka; perubahan bersamaan ditolak untuk diperiksa ulang; satuan bahan yang sudah digunakan dilindungi |
| Laporan | Riwayat POS sudah tampil tetapi pendapatan, analitik, performa, ekspor, atau belanja pelanggan belum menghitungnya | Seluruh query keuangan memakai aturan kelayakan transaksi yang sama; filter ringkasan mengikuti daftar |
| Pembayaran QRIS | Waktu pembayaran tercatat sebelum verifikasi; bukti yang hilang masih berpotensi diverifikasi | `paid_at` dicatat saat verifikasi; file bukti harus tersedia; verifikasi ulang tidak mengurangi stok lagi |
| Pengaturan QRIS | POS dan mobile dapat menampilkan sumber QRIS berbeda | Keduanya membaca pengaturan toko yang sama; penggantian file diselesaikan sebelum file lama dihapus |
| Antrean | Pengambilan di mobile tidak memperbarui halaman antrean kasir yang sudah terbuka | Pemeriksaan revisi setiap 15 detik, saat tab aktif kembali, dan saat event pesanan diterima; pembaruan ditunda ketika dialog terbuka atau kasir sedang mengetik |
| Notifikasi | Pengiriman gagal bisa terlihat seperti transaksi gagal; notifikasi lama bisa dikirim setelah status berubah | Dispatch setelah commit dengan pelaporan kegagalan terpisah; job yang statusnya sudah usang dilewati |
| Akses akun | Reset password belum membatalkan seluruh akses lama; penonaktifan kasir meninggalkan antrean | Token dan sesi lama dicabut; akun kasir dengan pesanan aktif tidak dapat dinonaktifkan |
| Tampilan POS | Filter pencarian dan kategori saling menimpa; modal tertutup panel keranjang; nama produk dimasukkan sebagai HTML | Filter digabung, dialog diperbaiki lapisannya, dan teks produk/bahan/struk ditampilkan sebagai teks aman |
| Filter laporan | Tanggal kosong, tidak valid, atau terbalik menghasilkan kueri bermasalah | Validasi bersama dan nilai default untuk kolom tanggal kosong |

Perbaikan formulir edit produk juga diverifikasi saat kategori produk sudah
nonaktif: kategori yang sedang dipakai tetap tersedia di formulir. Kesalahan
pemanggilan query pada implementasi awal audit ditemukan saat pemeriksaan
halaman, dikoreksi, dan ditambahkan ke tes regresi sebelum pekerjaan ditutup.

## Hasil verifikasi

Semua transaksi pengujian dibuat pada database terpisah. Tes aplikasi menolak
berjalan jika database bukan SQLite `:memory:` atau database khusus dengan
awalan `cikscoffee_audit_`. Pengujian bersamaan juga memeriksa batas ini sebelum
menjalankan migrasi pengujian.

| Pemeriksaan | Hasil |
| --- | --- |
| Build aset produksi Vite | Lulus |
| Suite aplikasi pada SQLite | **98 tes, 698 assertion, lulus** |
| Suite aplikasi pada MySQL 8.0.30 terisolasi | **98 tes, 698 assertion, lulus** |
| Empat skenario dengan proses/koneksi database bersamaan | **4 tes, 29 assertion, lulus** |
| Validasi arsip deployment Python | **7 tes, lulus** |
| Sintaks PHP dan pemeriksaan format Pint pada file yang berubah | Lulus |
| Parsing workflow YAML dan sintaks delapan langkah Bash | Lulus |
| Halaman/endpoint pemilik dan karyawan pada preview terisolasi | **27 respons HTTP 200** |
| Skenario browser Chrome pada ukuran desktop dan layar kecil | Lulus |
| HTTPS publik `https://cikscoffee.biz.id/up` | HTTP 200 |

Suite SQLite dan MySQL berisi kasus yang sama, bukan 196 kasus berbeda.
Lingkungan lokal memakai PHP 8.5.9. Workflow tetap memakai PHP 8.3 dan sekarang
menjalankan suite serta tes bersamaan pada MariaDB 10.11 sebelum deployment.
Konfigurasi workflow ini belum dijalankan di GitHub untuk perubahan lokal ini;
kelulusan MariaDB/PHP 8.3 harus dilihat pada run setelah push.

Preview SQLite menggunakan fungsi HOUR khusus di harness pengujian untuk
meniru query analitik MySQL. Query tersebut juga diuji pada MySQL sungguhan.
Harness preview dan autentikasi otomatisnya berada di folder sementara privat,
bukan bagian aplikasi atau paket deployment.

### Empat skenario bersamaan

1. Dua kasir membeli stok satu porsi: tepat satu transaksi berhasil.
2. Dua permintaan identik dengan kunci pembayaran sama: satu pesanan dan satu
   pengurangan stok, kedua respons menunjuk pesanan yang sama.
3. Transaksi pertama pada hari yang sama dengan produk/bahan berbeda:
   menghasilkan nomor 0001 dan 0002 tanpa bentrok.
4. Checkout POS bersamaan dengan verifikasi QRIS mobile untuk bahan terakhir:
   hanya satu pemakaian stok yang berhasil.

### Skenario browser

- Pencarian bersama filter kategori dan penggabungan stok bahan dalam keranjang.
- Perubahan harga oleh pemilik saat dialog pembayaran kasir sudah terbuka.
- Batas jumlah pesanan dan posisi modal pembayaran di atas panel keranjang.
- Klik Bayar berulang, respons dibuang setelah server menyimpan transaksi,
  muat ulang halaman, lalu retry: tetap satu pesanan dan satu pengurangan stok.
- Struk pada POS dan riwayat pemilik, termasuk nama produk berisi markup uji.
- Tombol pickup API pelanggan menghilangkan pesanan dari antrean web otomatis.

Bukti lokal berada di `../../deployment-artifacts/pos-audit/`: `sqlite-tests.txt`,
`mysql-tests.txt`, `concurrency-tests.txt`, `browser-checks.txt`, `page-smoke.txt`,
dan empat tangkapan layar. Data di bukti browser adalah data uji.

## Penerapan melalui CI/CD

Ada **empat migrasi tambahan**:

1. `2026_10_25_000001_create_order_number_sequences_table.php`
2. `2026_10_25_000002_add_checkout_replay_to_orders_table.php`
3. `2026_10_25_000003_create_order_item_ingredients_table.php`
4. `2026_10_25_000004_add_payment_proof_hash_to_orders_table.php`

Tidak perlu menjalankan seeder atau mengosongkan database. Migrasi menambah
counter, metadata retry, snapshot resep, dan hash bukti. Kolom pada pesanan
lama bersifat nullable; migrasi tidak menghitung ulang transaksi atau stok lama.

Setelah perubahan web di-commit dan di-push ke `main`, workflow menjalankan
build, tes SQLite, tes MariaDB, tes bersamaan, dan pemeriksaan arsip. Deployment
bergantung pada kelulusan job build. Skrip deployment Docker yang sudah ada
menjalankan backup, mode maintenance, `migrate --force`, optimasi, pergantian
release, restart layanan, dan pemeriksaan kesehatan. Skrip tersebut tidak diubah
pada audit ini. Rollback kode tidak otomatis membalik migrasi database.

Setelah run berhasil, pemeriksaan produksi yang diperlukan adalah transaksi
POS cash/QRIS, verifikasi mobile hingga pickup, kesesuaian laporan, serta QRIS
dan bukti pembayaran. Pemeriksaan dengan transaksi sungguhan belum dilakukan
pada pekerjaan ini. Aplikasi mobile saat ini sudah memakai QRIS; bentuk respons
API dipertahankan dan tidak ada perubahan source APK pada audit web ini.

## Batas verifikasi dan perilaku yang masih berlaku

- QRIS masih diverifikasi manual oleh kasir. Unggahan gambar bukan konfirmasi
  otomatis bahwa uang sudah diterima. Integrasi payment gateway belum dibuat.
- Stok mobile diperiksa saat membuat pesanan, tetapi belum dipesan khusus.
  Pengurangan dilakukan saat verifikasi. Jika stok sudah terjual oleh POS,
  verifikasi ditolak; kebijakan reservasi, pembatalan, dan refund memerlukan
  pekerjaan tersendiri.
- Snapshot resep berlaku untuk item baru. Pesanan lama tanpa snapshot memakai
  resep yang tersedia saat verifikasi, lalu menyimpannya. Resep historis yang
  sudah ditimpa sebelum migrasi tidak bisa direkonstruksi dari data saat ini.
- Hash bukti berlaku untuk pesanan baru. Bukti transaksi lama tidak di-backfill.
- Penyimpanan retry POS menggunakan sessionStorage pada browser/tab kasir.
  Menghapus penyimpanan browser atau berpindah perangkat dapat menghilangkan
  pemulihan otomatis; periksa riwayat sebelum memasukkan ulang pembayaran.
- Pengiriman push ke perangkat fisik, pembayaran bank sebenarnya, dan uji beban
  jangka panjang VPS tidak diverifikasi oleh pengujian lokal ini.
- Database produksi belum diperiksa pada tahap akhir. Upaya pemeriksaan SSH
  baca-saja ditolak pemeriksaan persetujuan otomatis dengan alasan
  `blocked by policy`. HTTP 200 pada `/up` hanya membuktikan endpoint kesehatan
  publik merespons, bukan bahwa seluruh data transaksi produksi sudah konsisten.
- Tidak ada perubahan pada konfigurasi, container, akun VPS lain, atau data
  produksi dalam pekerjaan ini.
- Server preview dan database MySQL pengujian sudah dihentikan. Berkas
  sementara privat dipertahankan karena pemeriksaan persetujuan otomatis
  menolak langkah penghapusannya dengan alasan `blocked by policy`.
