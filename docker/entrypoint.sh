#!/bin/bash
set -e

# Terapkan migrasi database sebelum aplikasi menerima trafik. --force wajib
# untuk lingkungan non-interaktif dan perintah ini idempoten pada deploy ulang.
php /var/www/html/artisan migrate --force --no-interaction

# Bukti QRIS lama sebelumnya berada di disk public. Pindahkan satu kali ke
# storage private agar symlink public/storage tidak lagi dapat menyajikannya.
legacy_proofs="/var/www/html/storage/app/public/payment_proofs"
private_proofs="/var/www/html/storage/app/private/payment_proofs"
if [ -d "$legacy_proofs" ]; then
    mkdir -p "$private_proofs"
    for proof in "$legacy_proofs"/*; do
        [ -f "$proof" ] || continue
        target="$private_proofs/$(basename "$proof")"
        if [ -e "$target" ]; then
            rm -f "$proof"
        else
            mv "$proof" "$target"
        fi
    done
    rmdir "$legacy_proofs" 2>/dev/null || true
fi

# Buat symlink public/storage -> storage/app/public (agar gambar ter-serve langsung oleh nginx,
# sama seperti junction di lingkungan lokal Windows). Idempoten: aman dijalankan berulang.
php /var/www/html/artisan storage:link --no-interaction || true

# Image baru harus selalu merender template, route, dan konfigurasi dari rilis yang sama.
# Bersihkan artefak cache Laravel sebelum PHP-FPM dimulai agar perubahan deploy langsung terlihat.
php /var/www/html/artisan optimize:clear --no-interaction || true

# Pastikan permission storage dan bootstrap/cache benar
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Jalankan supervisord
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
