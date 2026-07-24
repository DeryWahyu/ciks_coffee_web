#!/bin/bash
set -e

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
