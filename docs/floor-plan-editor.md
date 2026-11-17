# Editor denah Ciks Coffee

Halaman menggunakan satu kanvas tampak atas dan panel properti di kanan.
Pemilik dan karyawan memakai editor serta alur penyimpanan yang sama lewat
menu masing-masing. Karyawan dapat menambah, mengubah detail/posisi, menghapus
meja tanpa riwayat, serta mengarsipkan dan mengaktifkan kembali meja.
Riwayat audit lengkap tetap berada di menu pemilik.
Seluruh card Navigasi/Tambahkan beserta alat dan daftar objeknya sudah
dihapus. Semua kontrol mengikuti warna tema web: espresso,
caramel, latte, dan cream.

## Penggunaan

Untuk mengatur kursi, pilih meja → **Ubah detail** → **Posisi kursi** →
**Atur setiap kursi**. Pilih Kiri, Kanan, Depan (bawah), atau Belakang (atas)
untuk setiap kursi lalu simpan detail. Pratinjau langsung mengikuti pilihan,
bentuk, dan rotasi meja. Sisi dihitung sebelum meja diputar. Kursi ditata merata
pada tepi yang dipilih; kursi tidak mempunyai koordinat bebas yang dapat membuatnya
terlepas dari meja. Mengubah posisi kursi tidak menggeser atau memutar meja.

Jumlah pilihan mengikuti kapasitas meja, maksimal 20. **Otomatis** mengembalikan
susunan bawaan. Pemilik dan karyawan mempunyai kontrol yang sama. Posisi tersimpan
pada `coffee_tables.chair_positions`, ikut diekspor, dan dikirim ke aplikasi mobile
melalui API denah yang sama. Perubahan memakai pengecekan versi meja agar tidak
menimpa pembaruan pengguna lain. Jika kapasitas diubah oleh klien lama tanpa data
kursi, susunan kembali otomatis agar jumlah kursi tetap benar.

Migrasi tambahan: `2026_11_19_000001_add_chair_positions_to_coffee_tables`.
Migrasi lokal menambah kolom JSON nullable tanpa mengubah meja yang sudah ada.
Pengujian mencakup penyimpanan kedua role, konflik versi, validasi sisi/jumlah,
ekspor, kesamaan geometri mobile, serta editor browser pada desktop dan ponsel.

- Gunakan Tambah meja pada header untuk membuat meja.
- Geser objek; tarik titik sudut untuk mengubah ukuran. Panel Properti
  mengatur label, koordinat, ukuran, rotasi, dan ukuran teks.
- Warna objek tersedia dari palet tema web, termasuk pilihan tanpa warna.
- Duplikat, hapus, serta Ke depan/Ke belakang mengatur objek dekoratif.
- Pilih meja atau penanda yang sudah ada dengan menekannya langsung pada denah.
- Pengaturan kanvas, grid, snap, undo/redo, dan zoom tersedia di editor.
- Meja ditambah lewat formulir agar nomor, kapasitas, status, hak akses,
  serta riwayat operasional tetap dipertahankan.

Pada denah karyawan, warna status hanya ditampilkan sebagai titik di sudut
meja. Latar meja netral; nomor dan kapasitas tetap terlihat. Arti warna
tersedia pada ringkasan status, tooltip, dan detail meja.

Mode monitoring memuat pembaruan setiap 8 detik dan saat halaman kembali
aktif. Pembaruan otomatis berhenti ketika editor, formulir, atau penyimpanan
sedang digunakan agar perubahan lokal tidak tertimpa. Versi meja tetap
diperiksa saat menyimpan untuk menolak perubahan berdasarkan data lama.

Shortcut: V pilih, H geser kanvas, Ctrl/Cmd+Z undo, Ctrl/Cmd+Shift+Z atau
Ctrl/Cmd+Y redo, Ctrl/Cmd+D duplikat, Delete hapus penanda, Ctrl/Cmd+S simpan.

## Hapus meja dan notifikasi

Aktifkan Edit denah, pilih meja, lalu gunakan Hapus meja pada toolbar atau
panel properti. Tombol yang sama tersedia pada pop-up Ubah detail untuk meja
tersimpan, termasuk data lama yang pernah dibuat seeder. Pembuatan meja baru
tidak menampilkan tombol hapus di formulirnya.

Penghapusan meminta konfirmasi dan langsung memperbarui denah serta jumlah
meja, tanpa memerlukan Simpan denah lagi. Meja yang sudah memiliki riwayat
status tetap harus diarsipkan agar jejak operasional tidak terhapus.

Notifikasi bersama untuk sukses, gagal, peringatan, dan informasi tidak
memiliki garis tepi/aksen berwarna. Ikon dan teks status tetap tersedia.

## Data awal

Latar bawaan memakai PNG hasil imagegen pada
`public/images/floor-plans/cikscoffee-floor-plan.png`, diekspor pada ukuran
3508 × 2481 piksel sesuai `Denah Ciks.jpg`. Seluruh margin foto dipertahankan.
Posisi kasir, dapur, jendela, pintu, dan toilet menjadi bagian latar tetap;
meja dan penanda editor tetap berupa objek interaktif terpisah.

Konfigurasi aset ada pada `config/floor_plan.php`. Web pemilik dan karyawan
memakai gambar yang sama, dan API menyertakan metadata background_image.
Gambar ditampilkan dengan contain tanpa pengulangan atau pemotongan. Grid
bisa ditampilkan di atasnya melalui pengaturan editor.

Pada zoom Pas (100%), kanvas memenuhi lebar card tanpa padding. Tinggi wadah
mengikuti rasio denah agar tidak menyisakan ruang kosong di bawah. Zoom lebih
besar tetap dapat digeser di dalam wadah tanpa mengubah proporsi denah.

Migrasi baru menyamakan kanvas yang masih kosong dan memakai ukuran awal
1200 × 800 ke ukuran gambar. Denah yang sudah berisi meja/penanda atau memakai
ukuran khusus tidak diubah. Tombol Ukuran gambar pada panel properti dapat
digunakan untuk menyesuaikan kanvas tersebut secara manual, lalu disimpan.

Seeder meja dan data koordinat sketsa sudah dihapus. DatabaseSeeder hanya
memanggil seeder menu produk. Migrasi inisialisasi denah kosong tetap tersedia
agar editor dapat dimuat tanpa menambahkan meja contoh.

Penghapusan seeder tidak menghapus data operasional yang sudah ada. Pratinjau
editor memakai database SQLite terpisah tanpa meja contoh, dengan latar denah.
Database pratinjau sketsa sebelumnya tetap disimpan dan tidak di-reset.

## Kompatibilitas

Penanda disimpan pada background_config.elements. Posisi penanda memakai
titik pusat; posisi meja memakai titik kiri atas, dalam persen kanvas.
Penyimpanan layout tetap mendukung denah tanpa meja. Renderer web pemilik
dan karyawan berbagi komponen yang sama.

Nilai warna lama tetap dibaca menggunakan warna tema. Metadata dekorasi meja
yang sudah tersimpan tetap didukung agar data lama dapat ditampilkan.
APK Flutter belum diubah dalam pekerjaan editor web lokal ini.

## Pratinjau lokal

http://127.0.0.1:9044/pemilik/meja

Akun pratinjau: pemilik@tables.audit / private-table-audit.
Launcher: deployment-artifacts/table-reference/start-preview.ps1 di workspace.
Database: LOCALAPPDATA/CiksCoffee/layout-editor-preview/preview.sqlite.
Tidak ada commit, push, atau deployment dalam pengembalian tampilan ini.
