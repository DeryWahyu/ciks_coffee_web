#!/usr/bin/env bash
# Install only CiksCoffee's restricted CI command; leave all other users alone.
set -euo pipefail
[[ $(id -u) == 0 ]] || exit 1
source_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
web_key=${1:?Web public key required}
mobile_key=${2:?Mobile public key required}
for key in "$web_key" "$mobile_key"; do
    grep -Eq '^ssh-ed25519 [A-Za-z0-9+/=]+( |$)' "$key"
    [[ $(wc -l < "$key") == 1 ]] || exit 1
done
install -d -o root -g root -m 0755 /usr/local/lib/cikscoffee-dery
install -o root -g root -m 0755 "$source_dir/ci-ssh.sh" /usr/local/lib/cikscoffee-dery/ci-ssh
install -o root -g root -m 0755 "$source_dir/host-ci.sh" /usr/local/sbin/cikscoffee-dery-ci
install -d -o dery -g dery -m 0700 /home/dery/.ssh
authorized=/home/dery/.ssh/authorized_keys
if [[ -e "$authorized" ]]; then
    cp -p "$authorized" "/home/dery/cikscoffee/migration/authorized_keys.before-$(date -u +%Y%m%dT%H%M%SZ)"
fi
touch "$authorized"
for mode in web mobile; do
    keyfile=$web_key
    [[ "$mode" == web ]] || keyfile=$mobile_key
    fingerprint=$(awk '{print $2}' "$keyfile")
    if ! grep -Fq "$fingerprint" "$authorized"; then
        printf 'restrict,command="/usr/local/lib/cikscoffee-dery/ci-ssh %s" ' "$mode" >> "$authorized"
        cat "$keyfile" >> "$authorized"
    fi
done
chown dery:dery "$authorized"
chmod 0600 "$authorized"
printf '%s\n' 'dery ALL=(root) NOPASSWD: /usr/local/sbin/cikscoffee-dery-ci *' > /etc/sudoers.d/cikscoffee-dery-ci
chmod 0440 /etc/sudoers.d/cikscoffee-dery-ci
visudo -cf /etc/sudoers.d/cikscoffee-dery-ci
sshd -t
echo 'Restricted Docker CI/CD access installed for dery'
