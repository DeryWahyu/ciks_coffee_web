#!/usr/bin/env bash
set -euo pipefail
umask 0027
app=/var/www/cikscoffee
archive=${1:-/root/cikscoffee-deploy/app.tar.gz}

tar --no-same-owner -xzf "$archive" -C "$app"
install -d -m 2770 -o www-data -g www-data "$app/storage/app/private/firebase" \
    "$app/storage/app/public" "$app/storage/framework/cache/data" \
    "$app/storage/framework/sessions" "$app/storage/framework/views" \
    "$app/storage/logs" "$app/bootstrap/cache"

python3 - <<'PY'
import base64, pathlib, secrets, subprocess
envfile = pathlib.Path('/var/www/cikscoffee/.env')
if envfile.exists():
    data = dict(line.split('=',1) for line in envfile.read_text().splitlines() if '=' in line and not line.startswith('#'))
    dbpassword = data['DB_PASSWORD'].strip('"')
else:
    dbpassword = secrets.token_hex(24)
    data = {
        'APP_NAME':'"Ciks Coffee"', 'APP_ENV':'production', 'APP_DEBUG':'false',
        'APP_KEY':'base64:'+base64.b64encode(secrets.token_bytes(32)).decode(),
        'APP_URL':'https://cikscoffee.biz.id', 'APP_TIMEZONE':'Asia/Jakarta',
        'APP_LOCALE':'id', 'APP_FALLBACK_LOCALE':'id', 'APP_FAKER_LOCALE':'id_ID',
        'LOG_CHANNEL':'daily', 'LOG_LEVEL':'warning', 'LOG_DAILY_DAYS':'7',
        'DB_CONNECTION':'mysql', 'DB_HOST':'127.0.0.1', 'DB_PORT':'3306',
        'DB_DATABASE':'cikscoffee', 'DB_USERNAME':'cikscoffee_app', 'DB_PASSWORD':dbpassword,
        'SESSION_DRIVER':'database', 'SESSION_LIFETIME':'120', 'SESSION_SECURE_COOKIE':'true',
        'SESSION_HTTP_ONLY':'true', 'SESSION_SAME_SITE':'lax',
        'CACHE_STORE':'database', 'QUEUE_CONNECTION':'database', 'DB_QUEUE_RETRY_AFTER':'90',
        'BROADCAST_CONNECTION':'reverb', 'REVERB_APP_ID':str(secrets.randbelow(900000)+100000),
        'REVERB_APP_KEY':secrets.token_hex(16), 'REVERB_APP_SECRET':secrets.token_hex(32),
        'REVERB_SERVER_HOST':'127.0.0.1', 'REVERB_SERVER_PORT':'8080',
        'REVERB_HOST':'127.0.0.1', 'REVERB_PORT':'8080', 'REVERB_SCHEME':'http',
        'REVERB_ALLOWED_ORIGINS':'cikscoffee.biz.id,www.cikscoffee.biz.id',
        'FILESYSTEM_DISK':'local', 'SANCTUM_EXPIRATION':'10080',
        'FIREBASE_PROJECT_ID':'ciks-coffee',
        'FIREBASE_CREDENTIALS':'/var/www/cikscoffee/storage/app/private/firebase/service-account.json',
        'MAIL_MAILER':'log', 'MAIL_FROM_ADDRESS':'"hello@cikscoffee.biz.id"',
        'MAIL_FROM_NAME':'"Ciks Coffee"', 'VITE_APP_NAME':'"Ciks Coffee"',
    }
    envfile.write_text(''.join(f'{k}={v}\n' for k,v in data.items()))
    envfile.chmod(0o640)
sql = f"""CREATE DATABASE IF NOT EXISTS cikscoffee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'cikscoffee_app'@'127.0.0.1' IDENTIFIED BY '{dbpassword}';
GRANT ALL PRIVILEGES ON cikscoffee.* TO 'cikscoffee_app'@'127.0.0.1';
"""
subprocess.run(['mariadb'], input=sql, text=True, check=True)
print('DATABASE_AND_ENV_READY')
PY

if [ -f /root/cikscoffee-deploy/service-account.json ]; then
    install -o www-data -g www-data -m 0640 /root/cikscoffee-deploy/service-account.json \
        "$app/storage/app/private/firebase/service-account.json"
fi
chown -R cikscoffee:www-data "$app"
find "$app" -type d -exec chmod 2750 {} +
find "$app" -type f -exec chmod 0640 {} +
chown -R www-data:www-data "$app/storage" "$app/bootstrap/cache"
find "$app/storage" "$app/bootstrap/cache" -type d -exec chmod 2770 {} +
find "$app/storage" "$app/bootstrap/cache" -type f -exec chmod 0660 {} +

cd "$app"
runuser -u cikscoffee -- env COMPOSER_HOME=/var/lib/cikscoffee/.composer composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader
runuser -u cikscoffee -- composer check-platform-reqs --no-dev
runuser -u www-data -- php artisan migrate --force --no-interaction
runuser -u cikscoffee -- php artisan storage:link --no-interaction
runuser -u www-data -- env CIKS_OWNER_EMAIL="${CIKS_OWNER_EMAIL:?Owner email required}" \
    CIKS_OWNER_PASSWORD="${CIKS_OWNER_PASSWORD:?Owner password required}" \
    php deployment/ubuntu/bootstrap-owner.php
runuser -u www-data -- php artisan optimize --no-interaction

install -m 0644 deployment/ubuntu/nginx.conf /etc/nginx/sites-available/cikscoffee
rm -f /etc/nginx/sites-enabled/default
ln -sfn /etc/nginx/sites-available/cikscoffee /etc/nginx/sites-enabled/cikscoffee
install -m 0644 deployment/ubuntu/supervisor.conf /etc/supervisor/conf.d/cikscoffee.conf
printf '%s\n' '* * * * * www-data cd /var/www/cikscoffee && /usr/bin/php artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/cikscoffee
chmod 0644 /etc/cron.d/cikscoffee
nginx -t
systemctl reload nginx
systemctl restart php8.3-fpm
supervisorctl reread
supervisorctl update
supervisorctl status
printf '\nAPP_INSTALL_OK\n'
