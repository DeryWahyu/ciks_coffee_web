# Audio notifikasi pesanan karyawan

- `bel-pesanan-ramai.wav`: bel sintetis orisinal, tiga rentetan denting logam
  bertumpuk dengan warble cepat. Mono PCM 16-bit, 24 kHz, 1,44 detik, 69.164 byte.
  Peak 0,96; RMS 0,531; tidak ada sampel yang mencapai batas clipping PCM.
- `pesanan-baru-id.mp3`: ucapan sintetis bahasa Indonesia,
  **"Ada pesanan baru masuk."**, voice `id-ID-ArdiNeural`, rate `-5%`, 14.832 byte.

Kedua file diputar dari domain aplikasi. Server dan browser pengguna tidak
memanggil layanan text-to-speech eksternal saat ada pesanan masuk.

Bel dibuat dengan Python standard library:

```powershell
python tools/generate_order_bell.py
```

Ucapan dibuat satu kali menggunakan [edge-tts](https://github.com/rany2/edge-tts)
versi 7.3.1 di virtual environment sementara:

```powershell
python -m edge_tts --voice id-ID-ArdiNeural --text "Ada pesanan baru masuk." --rate=-5% --volume=+0% --write-media public/audio/pesanan-baru-id.mp3
```

File audio sudah disertakan; dependency pembuat ucapan tidak diperlukan untuk
menjalankan aplikasi maupun workflow deployment.
