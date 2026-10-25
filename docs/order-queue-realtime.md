# Antrean karyawan tanpa reload halaman

Perubahan lokal, 25 Oktober 2026. Belum di-commit, di-push, atau di-deploy.

## Hasil

- Setelah pelanggan menekan **Sudah Ambil** di mobile, pesanan langsung hilang
  dari daftar antrean karyawan dan jumlah pada tab status diperbarui.
- Penambahan pesanan, proses kasir, perubahan penangan, dan status selesai juga
  memperbarui bagian antrean melalui event realtime.
- Pembaruan mengganti bagian yang berubah pada daftar, tab, dan pagination.
  Tidak ada reload dokumen untuk menjalankan pembaruan antrean.
- Kartu yang tidak berubah tetap menggunakan elemen DOM yang sama. Fokus pada
  kontrol kartu tersebut, modal bukti pembayaran/struk, dan pemutaran suara
  tetap berjalan ketika pesanan lain diperbarui.
- Jika halaman terakhir menjadi kosong, daftar berpindah ke halaman yang masih
  valid melalui penggantian isi dan URL, dengan filter status tetap dipakai.
- Semua aksen garis berwarna pada kartu notifikasi karyawan dihapus: toast
  informasi/sukses/peringatan/error, pesan flash sukses/gagal, serta garis kiri
  kartu pesanan pada antrean. Ikon dan teks tetap menjelaskan jenis notifikasi.

## Alur

`OrderObserver` menyiarkan event privat `OrderQueueUpdated` setelah transaksi
database berhasil commit, termasuk perubahan status menjadi `diambil`.
Broadcast langsung memakai `ShouldBroadcastNow` sehingga tidak menunggu worker
antrean. Kegagalan broadcast dilaporkan tanpa membatalkan pesanan yang sudah
tersimpan. Batas koneksi Reverb satu detik dan batas request tiga detik mencegah
gangguan transport menahan request terlalu lama.

Web karyawan menerima `order-queue.updated`, mengambil snapshot HTML dari
`/karyawan/orders/snapshot`, lalu memperbarui daftar dan jumlah status. HTML
dirender server sesuai hak akses karyawan yang sedang masuk; payload event
hanya berisi ID dan status pesanan. Snapshot menggunakan header
`Cache-Control: private, no-store`; data yang belum berubah membalas HTTP 204.

Pemeriksaan cadangan berjalan setiap lima detik saat tab terlihat, serta saat
koneksi/tab kembali aktif. Event yang tiba selama request berlangsung memicu
pemeriksaan berikutnya agar respons lama tidak meninggalkan antrean tertinggal.
Polling ini juga memperbarui DOM ketika WebSocket atau server Reverb gagal.
Frekuensi polling tetap mengikuti pembatasan timer browser dan kondisi jaringan.

Implementasi mengikuti [broadcast langsung Laravel](https://laravel.com/docs/13.x/broadcasting#broadcast-queue)
dan menjalankan pengiriman setelah commit, dengan pemulihan dari database bila
transport gagal. Aturan pickup dan riwayat transaksi tetap didokumentasikan di
[pengambilan pesanan mobile](mobile-order-pickup.md).

## Pengujian

| Pemeriksaan | Hasil |
| --- | --- |
| Suite aplikasi SQLite memory | 112 tes / 766 assertion, lulus |
| Suite JavaScript audio, notifikasi, dan antrean | 35 tes, lulus |
| Build Vite, sintaks PHP/JavaScript, dan Pint pada PHP yang berubah | Lulus |
| Login karyawan, API mobile, dan Reverb lokal sungguhan | Lulus |
| Pickup pelanggan tanpa reload, sambil audio masih berbunyi | Lulus |
| Tiga ucapan selesai tanpa terpotong pembaruan antrean | Lulus |
| Kartu/fokus yang tidak berubah dan modal bukti pembayaran tetap utuh | Lulus |
| Struk yang sedang terbuka tetap utuh saat pesanan baru datang | Lulus |
| Filter status, kondisi kosong, serta penggabungan halaman terakhir | Lulus |
| WebSocket karyawan terputus: pembaruan DOM melalui polling | Lulus |
| Proses Reverb dimatikan: pickup HTTP 200 dan antrean diperbarui tanpa reload | Lulus |
| Semua tipe toast karyawan tidak memiliki garis border berwarna | Lulus |

Dalam uji realtime utama, timer polling dimatikan pada browser pengujian agar
pembaruan terbukti berasal dari event WebSocket asli. Penanda dokumen tetap
sama, kartu yang tidak berubah mempertahankan identitas/fokus, dan suara tetap
menyelesaikan tiga ucapan. Uji gangguan terpisah mengaktifkan polling dengan
proses Reverb lokal benar-benar dihentikan. Tidak ada JavaScript page error.

Seluruh transaksi dan akun pengujian memakai database serta penyimpanan privat
terpisah. Artefak berada di `../../deployment-artifacts/queue-realtime/`:
`application-tests.txt`, `javascript-tests.txt`, `browser-checks.txt`,
`outage-checks.txt`, dan `employee-notifications-no-colored-lines.png`.

## Penerapan

Tidak ada migrasi database, seeder, atau perubahan source APK yang diperlukan
untuk perubahan antrean ini. Commit/push seluruh file baru dan perubahan web
agar workflow menjalankan pengujian dan deployment. Pemeriksaan di sini adalah
verifikasi lokal; run GitHub dan layanan produksi untuk versi ini belum diuji.
