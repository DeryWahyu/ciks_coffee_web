#!/usr/bin/env bash
# Run once as root after the native VPS bootstrap and initial app installation.
set -euo pipefail
umask 0027
[[ $(id -u) == 0 ]] || exit 1
source_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
web_key=${1:?Web SSH public key file required}
mobile_key=${2:?Mobile SSH public key file required}
app=/var/www/cikscoffee
releases=/var/www/cikscoffee-releases
shared=/var/www/cikscoffee-shared
test -s "$app/.env"
test -s "$web_key"
test -s "$mobile_key"
for key in "$web_key" "$mobile_key"; do
    grep -Eq '^ssh-ed25519 [A-Za-z0-9+/=]+( |$)' "$key"
    [[ $(wc -l < "$key") == 1 ]] || exit 1
done
for script in ci-ssh.sh ci-entry.sh deploy-release.sh; do bash -n "$source_dir/$script"; done
python3 -m py_compile "$source_dir/validate-release.py"
install -d -o root -g www-data -m 0750 "$releases" "$shared"
install -o root -g root -m 0750 "$source_dir/backup.sh" /usr/local/bin/cikscoffee-backup

if [[ ! -L "$app" ]]; then
    test ! -e "$shared/.env"
    test ! -e "$shared/storage"
    /usr/local/bin/cikscoffee-backup
    initial="$releases/initial-$(date -u +%Y%m%dT%H%M%SZ)"
    recover_initial() {
        result=$?
        trap - EXIT
        if [[ "$result" != 0 ]]; then
            set +e
            if [[ ! -e "$app" && -d "$initial" ]]; then ln -s "$initial" "$app"; fi
            for item in .env storage; do
                if [[ -e "$shared/$item" && ! -e "$app/$item" ]]; then ln -s "$shared/$item" "$app/$item"; fi
            done
            if [[ -e "$shared/downloads" && ! -e "$app/public/downloads" ]]; then
                ln -s "$shared/downloads" "$app/public/downloads"
            fi
            (cd "$app" && runuser -u www-data -- php artisan up --no-interaction)
            supervisorctl start cikscoffee-queue cikscoffee-reverb
        fi
        exit "$result"
    }
    trap recover_initial EXIT
    (cd "$app" && runuser -u www-data -- php artisan down --retry=15 --no-interaction)
    supervisorctl stop cikscoffee-queue cikscoffee-reverb
    mv "$app" "$initial"
    ln -s "$initial" "$app"
    mv "$initial/.env" "$shared/.env"
    ln -s "$shared/.env" "$initial/.env"
    mv "$initial/storage" "$shared/storage"
    ln -s "$shared/storage" "$initial/storage"
    if [[ -d "$initial/public/downloads" ]]; then
        mv "$initial/public/downloads" "$shared/downloads"
    else
        install -d -o cikscoffee -g www-data -m 0750 "$shared/downloads"
    fi
    ln -s "$shared/downloads" "$initial/public/downloads"
    (cd "$app" && runuser -u www-data -- php artisan up --no-interaction)
    systemctl reload php8.3-fpm
    supervisorctl start cikscoffee-queue cikscoffee-reverb
    trap - EXIT
fi
[[ $(readlink -f "$app") == "$releases/"* ]] || exit 1
if [[ ! -e "$shared/downloads/cikscoffee-latest.apk" && -s "$shared/downloads/CiksCoffee-1.0.0-production.apk" ]]; then
    ln -s CiksCoffee-1.0.0-production.apk "$shared/downloads/cikscoffee-latest.apk"
fi
install -d -m 0755 /usr/local/lib/cikscoffee
install -o root -g root -m 0755 "$source_dir/ci-ssh.sh" /usr/local/bin/cikscoffee-ci-ssh
install -o root -g root -m 0755 "$source_dir/ci-entry.sh" /usr/local/sbin/cikscoffee-ci-entry
install -o root -g root -m 0755 "$source_dir/deploy-release.sh" /usr/local/sbin/cikscoffee-deploy-release
install -o root -g root -m 0644 "$source_dir/validate-release.py" /usr/local/lib/cikscoffee/validate-release.py
if ! id ciks-deploy >/dev/null 2>&1; then
    useradd --create-home --home-dir /var/lib/cikscoffee-deploy --shell /bin/bash ciks-deploy
fi
chmod 0750 /var/lib/cikscoffee-deploy
install -d -o ciks-deploy -g ciks-deploy -m 0700 /var/lib/cikscoffee-deploy/.ssh
{
    printf 'restrict,command="/usr/local/bin/cikscoffee-ci-ssh web" '
    cat "$web_key"
    printf 'restrict,command="/usr/local/bin/cikscoffee-ci-ssh mobile" '
    cat "$mobile_key"
} > /var/lib/cikscoffee-deploy/.ssh/authorized_keys
chown ciks-deploy:ciks-deploy /var/lib/cikscoffee-deploy/.ssh/authorized_keys
chmod 0600 /var/lib/cikscoffee-deploy/.ssh/authorized_keys
printf '%s\n' 'ciks-deploy ALL=(root) NOPASSWD: /usr/local/sbin/cikscoffee-ci-entry *' > /etc/sudoers.d/cikscoffee-ci
chmod 0440 /etc/sudoers.d/cikscoffee-ci
visudo -cf /etc/sudoers.d/cikscoffee-ci
nginx_backup="/root/cikscoffee-deploy/nginx.before-ci-$(date -u +%Y%m%dT%H%M%SZ).conf"
cp -p /etc/nginx/sites-available/cikscoffee "$nginx_backup"
install -m 0644 "$source_dir/nginx.conf" /etc/nginx/sites-available/cikscoffee
if ! nginx -t; then
    cp -p "$nginx_backup" /etc/nginx/sites-available/cikscoffee
    exit 1
fi
systemctl reload nginx
sshd -t
curl --fail --silent --show-error --max-time 15 -H 'Host: cikscoffee.biz.id' -H 'X-Forwarded-Proto: https' http://127.0.0.1/up >/dev/null
echo 'Native VPS CI/CD access installed'
