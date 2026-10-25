# Notifikasi suara pesanan karyawan

Perubahan lokal, 25 Oktober 2026. Belum di-commit, di-push, atau di-deploy.

## Perilaku

Pesanan mobile QRIS baru memutar notifikasi pada web karyawan melalui channel
privat realtime, dengan pemeriksaan database sebagai jalur cadangan di semua
halaman karyawan. Pola suaranya:

1. Bel ramai dengan tiga rentetan dering cepat dan denting logam bertumpuk.
2. Ucapan bahasa Indonesia **"Ada pesanan baru masuk."**
3. Pasangan bel dan ucapan tersebut diulang sampai total **tiga kali**.

Bel dan ucapan dijadwalkan bergantian agar ucapan tetap jelas. Pesanan beruntun
diantrekan tanpa menumpuk suara. Event pesanan yang sama diabaikan. Pada browser
yang mendukung Web Locks dan localStorage, satu tab menahan lock selama seluruh
ucapan berlangsung. Notifikasi baru dicatat sebagai sudah dibunyikan setelah
ketiga ucapan selesai. Setiap perangkat memiliki pengaturan dan audionya sendiri.

## Perbaikan agar notifikasi tidak terlewat

- Sebelumnya notifikasi bergantung pada WebSocket dan job broadcast. Sekarang
  endpoint karyawan `/karyawan/orders/notifications` diperiksa setiap 5 detik,
  ketika halaman dibuka, koneksi pulih, dan tab kembali aktif. Pesanan mobile
  QRIS yang masih `menunggu_verifikasi` tetap ditemukan meskipun event hilang.
- Setiap pemeriksaan memulai kembali pemindaian pesanan yang menunggu. Cursor
  hanya dipakai untuk pagination dalam pemeriksaan tersebut. Transaksi yang
  commit belakangan dengan ID lebih kecil tetap ditemukan pada pemeriksaan berikutnya.
- Notifikasi yang tiba sebelum izin audio tidak dibuang. Notifikasi menunggu
  hingga audio diaktifkan. Setelah reload, pesanan yang belum diverifikasi dan
  belum selesai dibunyikan ditemukan kembali dari database.
- Pencatatan sebelum pemutaran selesai dihapus. Reload atau penutupan tab saat
  suara belum selesai tidak menandai pesanan sebagai sudah dibunyikan; tab lain
  atau halaman berikutnya bisa mengulangnya. Interupsi dapat mengulang bagian
  yang sebelumnya sudah terdengar, agar sisa pemberitahuan tidak hilang.
- Penolakan Web Locks tidak lagi menelan notifikasi diam-diam. Audio dicoba
  tanpa koordinasi lintas tab jika API tersebut tidak dapat digunakan. Dalam
  kondisi ini dua tab bisa sama-sama berbunyi.
- Percobaan izin audio tidak lagi terbatas pada klik/keyboard pertama. Interaksi
  berikutnya dapat memulihkan audio yang sebelumnya ditolak. Tombol aktivasi
  cepat terlihat pada header ketika suara belum aktif, dibisukan, atau volumenya nol.
- Pemulihan halaman dari back-forward cache memuat ulang halaman agar modul
  yang sudah dihentikan saat navigasi tidak tertinggal dalam keadaan mati.
- Pengambilan file audio menggunakan AbortController dengan batas waktu dan
  tidak bergantung pada ketersediaan `AbortSignal.timeout`. File dipersiapkan
  setelah izin audio diberikan; bel cadangan tetap tersedia.
- Data feed dan otorisasi channel dibatasi ke karyawan aktif. Sesi yang berakhir
  menghentikan polling dan meminta pengguna masuk ulang.

Feed hanya mengembalikan pesanan mobile QRIS yang masih menunggu verifikasi,
bukan transaksi POS atau pesanan yang sudah diproses. Respons menggunakan
`Cache-Control: private, no-store`; pagination dibatasi 50 pesanan per halaman.

File WAV bel dan MP3 ucapan berukuran total **83.996 byte**. Bel memiliki
durasi 1,44 detik dan dibuat oleh `tools/generate_order_bell.py`; asal serta
perintah pembuatan ucapan dicatat di `public/audio/README.md`. Pemutaran memakai
file lokal aplikasi, tanpa dependency layanan TTS saat runtime.

## Cara memakai

1. Masuk sebagai karyawan, lalu buka ikon lonceng pada header.
2. Klik **Aktifkan suara** pada header atau **Coba suara (3 kali)** di panel.
   Jika ada notifikasi menunggu, aktivasi memutarnya tanpa menambahkan preview kedua.
3. Atur volume aplikasi dan volume speaker perangkat. Volume awal untuk
   preferensi baru adalah 100%; pengaturan sebelumnya tetap dipertahankan.
4. Gunakan **Hentikan suara** untuk membatalkan bunyi berjalan beserta antrean
   bunyinya. Pesanan yang dihentikan sengaja tidak diputar kembali pada polling
   berikutnya. **Bisukan notifikasi** menunda audio hingga dinyalakan kembali;
   toast dan badge tetap muncul.

Klik pertama di halaman juga mencoba mengaktifkan audio. Preferensi disimpan
per akun pada browser. Pada pemuatan ulang, aplikasi mencoba memulihkan audio
yang sebelumnya diaktifkan; tombol aktivasi tetap tersedia jika browser
memblokirnya. Kebijakan ini mengikuti [aturan autoplay browser](https://developer.mozilla.org/en-US/docs/Web/Media/Guides/Autoplay).

Pembaruan otomatis halaman antrean menunggu suara selesai agar ketiga ucapan
tidak terpotong. Tombol berhenti dan bisu langsung membatalkan suara. Jika browser
menginterupsi audio, status pemutaran dilepas agar aktivasi dapat dicoba kembali.
Panel notifikasi juga dijaga agar tetap berada dalam batas layar kecil.

Jika file ucapan gagal dimuat, bel tetap diputar tiga kali. Jika file bel gagal,
bel sintetis cadangan dipakai. Status panel menyebutkan rekaman yang gagal dimuat.

## Verifikasi

- **29 tes JavaScript lulus**: audio, pengantaran dan pencatatan setelah selesai,
  dua tab, larangan storage/locks, interupsi, izin autoplay, polling berhalaman,
  jaringan putus, sesi berakhir, dan commit yang terlambat.
- **105 tes aplikasi / 732 assertion lulus** pada database SQLite memory,
  termasuk tujuh tes backend baru untuk akses role/channel, feed, pagination,
  checkout mobile asli, dan kegagalan transport notifikasi.
- Build Vite, sintaks JavaScript, parsing workflow YAML, dan delapan langkah
  Bash lulus pemeriksaan.
- Pengujian Chrome sebelumnya menguji file MP3/WAV asli dan tiga pemutaran lengkap, toast sebelum
  aktivasi audio, event ganda, dua tab karyawan, preferensi volume, kontrol
  berhenti/bisu, panel layar 390px, pembaruan antrean setelah semua ucapan
  selesai, serta kedua jalur kegagalan file. Tidak ada JavaScript page error.
- Pengujian keandalan memakai login/session sungguhan, API checkout mobile,
  file bukti PNG, database SQLite privat, dan server Reverb lokal sungguhan.
  Event WebSocket tidak diganti callback palsu. Skenario meliputi event privat
  hingga tiga ucapan selesai, WebSocket karyawan terputus, reload sebelum izin,
  reload di tengah suara, penolakan autoplay berulang, serta dua tab dengan
  penutupan tab yang sedang berbunyi.
- Uji gangguan mematikan proses Reverb lokal: checkout mobile tetap merespons
  HTTP 201, pemeriksaan database menemukan pesanan, dan ketiga ucapan selesai.
  Seluruh skenario keandalan lulus tanpa JavaScript page error. Ini adalah
  verifikasi lokal; koneksi HTTPS/WSS produksi belum diuji pada perubahan ini.
- Tes JavaScript ditambahkan ke workflow web sebelum pengujian aplikasi dan
  deployment. Run GitHub untuk perubahan ini belum berlangsung.

Bukti dan contoh gabungan yang direkam dari output audio browser berada di
`../../deployment-artifacts/order-notifications/`: `audio-tests.txt`,
`application-tests.txt`, `browser-checks.txt`, `employee-notification-mobile.png`,
dan `notifikasi-pesanan-3x.webm`. Versi WAV untuk pemutaran umum juga tersedia:
`notifikasi-pesanan-3x.wav`, durasi 13,08 detik. Ekspor WAV dinormalisasi agar
overshoot codec pada rekaman preview tidak menyebabkan clipping PCM.

Hasil pengujian keandalan terbaru berada di subfolder `reliability/`:
`javascript-tests.txt`, `application-tests.txt`, `primary-checks.txt`,
`outage-checks.txt`, dan `notification-recovered.png`.

## Penerapan

Ada satu migrasi tambahan `2026_10_25_000005_add_order_notification_index.php`
untuk indeks `orders(status, payment_method, id)`. Indeks mengurangi pekerjaan
pemindaian yang berulang. Tidak ada reset data atau seeder. Workflow deployment
yang sudah ada menjalankan migrasi saat versi ini di-push dan lolos pemeriksaan.
Pengujian MariaDB/PHP 8.3 di GitHub dan pemeriksaan produksi setelah deployment
belum dijalankan untuk perubahan lokal ini.

## Batas

Tidak ada perubahan API transaksi, source mobile, atau perubahan produksi pada
pekerjaan ini. Realtime memiliki jalur cadangan database; bila koneksi jaringan
dan browser mengizinkan, polling memeriksa setiap 5 detik. Halaman karyawan
harus tetap terbuka. Browser/perangkat yang tidur, tab yang
dibisukan, volume sistem yang nol, atau navigasi manual dapat menginterupsi audio.
Kerasnya speaker fisik tidak dapat dipaksakan dari kode web; pengujian browser
memverifikasi sinyal dan pemutaran, bukan tingkat suara di lokasi kasir.
