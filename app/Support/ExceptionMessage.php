<?php

namespace App\Support;

class ExceptionMessage
{
    public static function forStatus(int $status): array
    {
        return match ($status) {
            400 => ['Permintaan tidak sesuai', 'Permintaan tidak dapat dibaca. Muat ulang halaman lalu periksa kembali data yang dikirim.'],
            401 => ['Silakan masuk kembali', 'Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.'],
            403 => ['Akses tidak diizinkan', 'Anda tidak memiliki izin untuk melakukan tindakan ini. Kembali ke menu yang sesuai dengan akun Anda.'],
            404 => ['Data tidak ditemukan', 'Halaman atau data yang diminta tidak ditemukan. Muat ulang atau kembali ke menu sebelumnya.'],
            405 => ['Tindakan tidak tersedia', 'Tindakan ini tidak tersedia. Muat ulang halaman lalu coba melalui menu yang sesuai.'],
            408, 504 => ['Waktu tunggu habis', 'Server belum merespons. Periksa koneksi dan coba lagi. Jika sedang membayar, periksa status pesanan sebelum mengulang pembayaran.'],
            409 => ['Data telah berubah', 'Data telah berubah atau sudah diproses. Muat ulang dan periksa status pesanan sebelum mengulangi tindakan.'],
            410 => ['Data tidak tersedia lagi', 'Data yang diminta sudah tidak tersedia. Kembali ke menu sebelumnya lalu muat ulang data.'],
            413 => ['Berkas terlalu besar', 'Ukuran unggahan terlalu besar. Gunakan berkas yang lebih kecil lalu coba lagi.'],
            419 => ['Sesi formulir berakhir', 'Sesi formulir telah berakhir. Muat ulang halaman, lalu kirim kembali. Jika sedang membayar, periksa status pesanan terlebih dahulu.'],
            422 => ['Periksa kembali isian Anda', 'Data belum dapat diproses. Periksa kembali isian Anda.'],
            429 => ['Terlalu banyak percobaan', 'Terlalu banyak percobaan. Tunggu beberapa saat sebelum mencoba lagi.'],
            502, 503 => ['Layanan belum tersedia', 'Layanan sedang tidak tersedia. Silakan coba lagi beberapa saat. Jika sedang membayar, periksa status pesanan sebelum mengulang pembayaran.'],
            default => ['Terjadi kendala', 'Permintaan belum dapat diproses karena kendala pada server. Coba lagi beberapa saat. Jika sedang membayar, periksa status pesanan terlebih dahulu.'],
        };
    }
}
