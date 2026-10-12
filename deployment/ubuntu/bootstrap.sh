#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a

apt-get update
apt-get install -y --no-install-recommends ca-certificates curl gnupg unzip git \
    nginx mariadb-server supervisor ufw \
    php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-gd php8.3-zip php8.3-bcmath php8.3-sqlite3

install -d -m 0755 /usr/share/keyrings
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg -o /usr/share/keyrings/cloudflare-main.gpg
printf '%s\n' 'deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main' > /etc/apt/sources.list.d/cloudflared.list
apt-get update
apt-get install -y --no-install-recommends cloudflared

if ! command -v composer >/dev/null; then
    installer=$(mktemp)
    curl -fsSL https://getcomposer.org/installer -o "$installer"
    expected=$(curl -fsSL https://composer.github.io/installer.sig)
    actual=$(sha384sum "$installer" | cut -d ' ' -f 1)
    if [ "$expected" != "$actual" ]; then
        echo 'Composer installer checksum mismatch' >&2
        exit 1
    fi
    php "$installer" --install-dir=/usr/local/bin --filename=composer --quiet
    rm -f "$installer"
fi

if ! id cikscoffee >/dev/null 2>&1; then
    useradd --system --create-home --home-dir /var/lib/cikscoffee --shell /usr/sbin/nologin --gid www-data cikscoffee
fi
install -d -o cikscoffee -g www-data -m 2750 /var/www/cikscoffee
install -d -m 0700 /var/backups/cikscoffee /root/cikscoffee-deploy

if ! swapon --show=NAME --noheadings | grep -q .; then
    if [ ! -e /swapfile ]; then
        fallocate -l 1G /swapfile
        chmod 0600 /swapfile
        mkswap /swapfile
    fi
    swapon /swapfile
    grep -qE '^/swapfile[[:space:]]' /etc/fstab || printf '\n/swapfile none swap sw 0 0\n' >> /etc/fstab
fi
printf '%s\n' 'vm.swappiness=10' > /etc/sysctl.d/90-cikscoffee.conf
sysctl --system >/dev/null

cat > /etc/php/8.3/fpm/conf.d/99-cikscoffee.ini <<'EOF'
expose_php=Off
memory_limit=512M
max_execution_time=120
upload_max_filesize=8M
post_max_size=10M
opcache.enable=1
opcache.memory_consumption=96
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
EOF

cat > /etc/php/8.3/fpm/pool.d/www.conf <<'EOF'
[www]
user=www-data
group=www-data
listen=/run/php/php8.3-fpm.sock
listen.owner=www-data
listen.group=www-data
pm=ondemand
pm.max_children=2
pm.process_idle_timeout=15s
pm.max_requests=300
request_terminate_timeout=125s
catch_workers_output=yes
clear_env=yes
EOF

cat > /etc/mysql/mariadb.conf.d/60-cikscoffee.cnf <<'EOF'
[mysqld]
bind-address=127.0.0.1
innodb_buffer_pool_size=256M
max_connections=30
table_open_cache=400
tmp_table_size=16M
max_heap_table_size=16M
performance_schema=OFF
skip-name-resolve
EOF

# Bind the origin to loopback; public traffic will enter through cloudflared.
cat > /etc/nginx/sites-available/default <<'EOF'
server {
    listen 127.0.0.1:80 default_server;
    server_name _;
    return 503;
}
EOF
nginx -t
php-fpm8.3 -t
systemctl restart mariadb php8.3-fpm nginx
systemctl enable mariadb php8.3-fpm nginx supervisor

# SSH is forwarded by the provider from external 20253 to internal 22.
ufw allow 22/tcp comment 'SSH provider forwarding'
ufw default deny incoming
ufw default allow outgoing
ufw --force enable

printf '\nBOOTSTRAP_OK\n'
php --version | head -2
composer --version
cloudflared --version
free -m
df -h /
