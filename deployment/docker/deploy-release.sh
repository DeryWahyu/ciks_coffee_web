#!/usr/bin/env bash
set -euo pipefail
umask 0027
[[ $(id -u) == 0 ]] || exit 1
archive=${1:?Release archive required}
revision=${2:?Revision required}
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || exit 1
app=/var/www/cikscoffee
releases=/var/www/cikscoffee-releases
shared=/var/www/cikscoffee-shared
python3 /usr/local/lib/cikscoffee/validate-release.py "$archive" "$revision"
test -L "$app"
test -s "$shared/.env"
test -s "$shared/storage/app/private/firebase/service-account.json"
previous=$(readlink -f "$app")
[[ "$previous" == "$releases/"* && -d "$previous" ]] || exit 1
release="$releases/$revision"
if [[ -e "$release" ]]; then
    if [[ "$previous" == "$release" ]]; then
        curl --fail --silent --show-error --max-time 15 -H 'Host: cikscoffee.biz.id' -H 'X-Forwarded-Proto: https' http://127.0.0.1/up >/dev/null
        echo "Release already active: $revision"
        exit 0
    fi
    # Only an inactive directory owned by this release mechanism is replaced.
    python3 - "$release" "$releases" "$previous" <<'PY'
import pathlib, shutil, sys
candidate=pathlib.Path(sys.argv[1])
parent=pathlib.Path(sys.argv[2]).resolve()
active=pathlib.Path(sys.argv[3]).resolve()
if candidate.is_symlink() or candidate.resolve().parent != parent or candidate.resolve() == active:
    raise ValueError('Unsafe inactive release path')
shutil.rmtree(candidate)
PY
fi
install -d -o cikscoffee -g www-data -m 2750 "$release"
runuser -u cikscoffee -- tar --no-same-owner --no-same-permissions -xzf "$archive" -C "$release"
chown -R cikscoffee:www-data "$release"
find "$release" -type d -exec chmod 2750 {} +
find "$release" -type f -exec chmod 0640 {} +
ln -s "$shared/.env" "$release/.env"
ln -s "$shared/storage" "$release/storage"
ln -s "$shared/storage/app/public" "$release/public/storage"
ln -s "$shared/downloads" "$release/public/downloads"
install -d -o www-data -g www-data -m 2770 "$release/bootstrap/cache"
cd "$release"
runuser -u cikscoffee -- env COMPOSER_HOME=/var/lib/cikscoffee/.composer composer check-platform-reqs --no-dev
runuser -u www-data -- php artisan package:discover --no-interaction

maintenance=0
activated=0
recover() {
    result=$?
    trap - EXIT
    if [[ "$result" != 0 ]]; then
        set +e
        if [[ "$maintenance" == 1 ]]; then
            supervisorctl stop cikscoffee-queue cikscoffee-reverb
        fi
        if [[ "$activated" == 1 ]]; then
            ln -s "$previous" "$app.rollback"
            mv -Tf -- "$app.rollback" "$app"
            supervisorctl restart cikscoffee-php
        fi
        if [[ "$maintenance" == 1 ]]; then
            (cd "$app" && runuser -u www-data -- php artisan up --no-interaction)
            supervisorctl start cikscoffee-queue cikscoffee-reverb
        fi
        echo 'Deployment failed; previous code restored. Database migrations are not automatically reversed.' >&2
    fi
    exit "$result"
}
trap recover EXIT
/usr/local/bin/cikscoffee-backup
maintenance=1
(cd "$app" && runuser -u www-data -- php artisan down --retry=15 --no-interaction)
supervisorctl stop cikscoffee-queue cikscoffee-reverb
runuser -u www-data -- php artisan migrate --force --no-interaction
runuser -u www-data -- php artisan optimize --no-interaction
ln -s "$release" "$app.next"
mv -Tf -- "$app.next" "$app"
activated=1
supervisorctl restart cikscoffee-php
runuser -u www-data -- php artisan up --no-interaction
supervisorctl start cikscoffee-queue cikscoffee-reverb
curl --fail --silent --show-error --retry 3 --max-time 15 -H 'Host: cikscoffee.biz.id' -H 'X-Forwarded-Proto: https' http://127.0.0.1/up >/dev/null
service_status=$(supervisorctl status cikscoffee-queue cikscoffee-reverb)
[[ $(grep -c ' RUNNING ' <<< "$service_status") == 2 ]]
maintenance=0
trap - EXIT
printf '%s\n' "$previous" > "$shared/.previous-release"
echo "Release active: $revision"
python3 - "$releases" "$release" "$previous" <<'PY'
import pathlib, re, shutil, sys
directory=pathlib.Path(sys.argv[1]).resolve()
protected={pathlib.Path(p).resolve() for p in sys.argv[2:]}
releases=sorted((p for p in directory.iterdir() if p.is_dir() and not p.is_symlink()
                 and re.fullmatch(r'[0-9a-f]{40}',p.name)),
                key=lambda p:p.stat().st_mtime,reverse=True)
for release in releases[3:]:
    resolved=release.resolve()
    if resolved.parent != directory:
        raise ValueError('Unexpected release path')
    if resolved not in protected:
        shutil.rmtree(resolved)
PY
