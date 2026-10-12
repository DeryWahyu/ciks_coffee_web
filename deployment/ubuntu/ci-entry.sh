#!/usr/bin/env bash
set -euo pipefail
umask 0077
[[ $(id -u) == 0 && $# == 2 ]] || exit 1
mode=$1
command=$2
case "$mode" in web) operation=deploy;; mobile) operation=apk;; *) exit 1;; esac
if [[ "$command" == status ]]; then
    printf 'CI access ready: %s\n' "$mode"
    if [[ "$mode" == web ]]; then readlink /var/www/cikscoffee; fi
    exit 0
fi
[[ "$command" =~ ^${operation}\ ([0-9a-f]{40})\ ([0-9a-f]{64})$ ]] || {
    echo 'Unsupported deployment command' >&2; exit 1;
}
revision=${BASH_REMATCH[1]}
digest=${BASH_REMATCH[2]}
exec 9>/run/lock/cikscoffee-deploy.lock
flock -w 600 9
work=$(mktemp -d /var/tmp/cikscoffee-ci.XXXXXXXX)
trap 'rm -rf -- "$work"' EXIT
timeout 300 dd bs=1M count=128 iflag=fullblock of="$work/payload" status=none
printf '%s  %s\n' "$digest" "$work/payload" | sha256sum -c -
chmod 0644 "$work/payload"
chmod 0755 "$work"
if [[ "$mode" == web ]]; then
    /usr/local/sbin/cikscoffee-deploy-release "$work/payload" "$revision"
else
    python3 - "$work/payload" <<'PY'
import sys, zipfile
with zipfile.ZipFile(sys.argv[1]) as apk:
    names=set(apk.namelist())
    if not {'AndroidManifest.xml', 'classes.dex'}.issubset(names):
        raise ValueError('Not an Android package')
    if not any(name.endswith('/libapp.so') for name in names):
        raise ValueError('Flutter application missing')
PY
    downloads=/var/www/cikscoffee-shared/downloads
    filename="CiksCoffee-$revision-$digest.apk"
    install -o cikscoffee -g www-data -m 0644 "$work/payload" "$downloads/$filename.new"
    mv -f -- "$downloads/$filename.new" "$downloads/$filename"
    ln -sfn "$filename" "$downloads/.latest-next"
    mv -Tf -- "$downloads/.latest-next" "$downloads/cikscoffee-latest.apk"
    printf '%s %s\n' "$revision" "$digest" > /var/www/cikscoffee-shared/.mobile-revision
    python3 - "$downloads" <<'PY'
import pathlib, re, sys
directory=pathlib.Path(sys.argv[1]).resolve()
current=(directory/'cikscoffee-latest.apk').resolve()
files=sorted((p for p in directory.iterdir() if p.is_file() and not p.is_symlink()
              and re.fullmatch(r'CiksCoffee-[0-9a-f]{40}-[0-9a-f]{64}\.apk',p.name)),
             key=lambda p:p.stat().st_mtime, reverse=True)
for file in files[3:]:
    if file.resolve().parent != directory:
        raise ValueError('Unexpected APK path')
    if file.resolve() != current:
        file.unlink()
PY
    echo "APK published: $revision https://cikscoffee.biz.id/downloads/cikscoffee-latest.apk"
fi
