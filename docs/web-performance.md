# Optimasi web

Perubahan ini menargetkan unduhan gambar, query laporan dan pekerjaan berulang
pada browser/server. Tampilan, alur checkout, penguncian stok dan aturan pickup
tetap menggunakan sumber data yang sama. Tidak ada cache nilai stok/transaksi.

## Gambar

`php artisan media:optimize` menyiapkan versi produk dengan lebar maksimal
384 dan 768 piksel serta denah pada ukuran asli 3508 x 2481. Gambar asli
tetap disimpan untuk arsip. Kartu memakai `srcset`, `sizes`, lazy loading dan
decoding async; detail produk menggunakan versi 768 piksel. Gambar kecil
tidak diperbesar. Foto JPEG mengikuti orientasi EXIF.

Turunan berada pada disk publik di `optimized-media/`, dengan nama versi
berdasarkan sumber dan metadata berkas. Aplikasi menyimpan upload baru dengan
nama unik. Perubahan gambar menghasilkan URL baru. Pembuatan dilakukan pada
upload atau perintah CLI, bukan pada setiap permintaan halaman. Penghapusan
gambar produk juga membersihkan turunannya.

WebP digunakan jika GD mendukungnya; JPEG tersedia sebagai fallback. Foto
JPEG pada runtime tanpa EXIF menggunakan gambar asli agar orientasinya aman.
Runtime Docker telah disiapkan dengan WebP dan EXIF. Runtime lama tetap dapat
melayani sumber asli/fallback. Skrip deploy menyiapkan gambar sesudah migrasi.
Konfigurasi runtime tersebut baru berlaku setelah image Docker dibangun ulang.

## Laporan

Agregasi per tanggal menggantikan query di setiap iterasi hari. Total omzet,
jumlah pesanan dan pelanggan dihitung bersama. Hari tanpa transaksi tetap
ditampilkan dengan nilai nol. Batas rentang memakai `>= awal hari` dan
`< awal hari berikutnya`, dan indeks `orders.created_at` mendukung penyaringan.
Grafik, KPI dan omzet tetap memakai `visibleInTransactionHistory()`.

## Realtime

Kode Pusher/Echo, audio dan antrean dimuat sebagai chunk karyawan. Login dan
pemilik hanya memuat entry JS umum yang kecil.

Sesudah private channel berhasil subscribe, polling notifikasi/antrean
menjadi cadangan setiap 30 detik. Sebelum tersambung atau saat koneksi mulai
putus/connecting/error, interval kembali lima detik dan pemeriksaan langsung
dijalankan. Event WebSocket tetap memperbarui notifikasi segera; event antrean
yang berdekatan digabung dalam jeda 100 ms. Notifikasi tetap aktif pada tab
latar. Pemindaian cadangan tetap dimulai dari awal agar commit terlambat
atau event yang terlewat tidak hilang.

Pemeriksaan stok (15 detik) dan denah (8 detik) mempertahankan kesegarannya.
Server menghitung kondisi terkini lalu mengembalikan ETag privat. Jika belum
berubah, respons 304 mencegah pengiriman JSON dan pembentukan ulang DOM.
Permintaan yang mengubah data dan pemeriksaan checkout tetap seperti biasa.

## Cache dan konfigurasi server

Gambar publik menggunakan respons file tanpa session/cookie, ETag dan
Last-Modified. Turunan berversi dapat di-cache setahun; sumber publik sehari.
Route publik menolak path traversal dan bukti pembayaran, termasuk lokasi
publik lama. Disk privat tidak membuka endpoint storage umum.

Halaman aplikasi dan API menggunakan `private, no-store`. Cloudflare harus
mengikuti header origin; jangan menimpa aturan ini dengan Cache Everything
untuk halaman login, role, stok, transaksi, API atau bukti pembayaran.

Konfigurasi Nginx menambahkan expiry aset publik serta gzip untuk HTML, JSON,
CSS, JS dan SVG. Gambar WebP/JPEG tidak dikompresi ulang dengan gzip. OPcache,
optimasi Laravel, jumlah worker PHP dan batas memori container dipertahankan.
Pengaturan worker/database perlu hasil pengukuran beban server sebelum diubah.
Tidak ada perubahan layanan atau konfigurasi Cloudflare/VPS saat pekerjaan lokal.

## Hasil pengukuran lokal

Pembanding: commit `1e12fa6`, PHP 8.5.9 dan database MySQL lokal yang sama.
Angka query adalah pemanggilan controller; waktu/CPU VPS tidak disimpulkan
dari database lokal yang berisi sedikit transaksi.

| Ukuran | Sebelum | Sesudah |
|---|---:|---:|
| Query analisis 7 hari | 23 | 6 |
| Query analisis 30 hari | 69 | 6 |
| Query analisis 90 hari | 189 | 6 |
| Query dashboard pemilik | 24 | 10 |
| Gambar asli katalog, 18 produk | 36,66 MiB | asli tetap disimpan |
| Versi gambar produk, dua ukuran | - | sekitar 1,08 MiB total |
| Gambar denah yang dikirim | 2.253.802 byte | 92.750 byte |
| Entry JavaScript umum | 90.119 byte | sekitar 1.648 byte |

Chunk karyawan sekitar 91 KB tetap dimuat pada role karyawan yang membutuhkannya.
Angka JS di atas adalah sebelum kompresi transfer, bukan ukuran seluruh halaman.
Browser hanya memilih ukuran gambar yang sesuai; kedua ukuran tidak wajib
diunduh bersamaan. Penyimpanan bertambah karena sumber asli tetap dipertahankan.

Pengujian build produksi menggunakan WebSocket tiruan: selama 11 detik idle
setelah subscribe tidak ada polling feed/antrean; notifikasi event muncul segera,
dan putus koneksi mengembalikan pemeriksaan cepat. Ini pengujian alur lokal,
bukan pengukuran latensi internet atau beban produksi.

## Verifikasi

```powershell
php artisan migrate
php artisan media:optimize
npm run build
php artisan test
npm run test:notifications
```

Tes memeriksa batas tanggal dan hari kosong, konsistensi pickup, jumlah query
yang tidak bertambah dengan panjang periode, dimensi/orientasi gambar,
keutuhan sumber, ETag setelah stok/status meja berubah, akses role dan
pemisahan cache publik/privat. Pengujian database menggunakan isolasi pada
`tests/TestCase.php`. Konfigurasi cache/gzip Nginx diperiksa dengan `nginx -t`
dalam konfigurasi uji lokal yang menggunakan path Windows.
