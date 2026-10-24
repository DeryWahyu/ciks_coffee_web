#!/usr/bin/env bash
set -euo pipefail
[[ $(id -u) == 0 && $# == 2 ]] || exit 1
mode=$1
command=$2
case "$mode" in web) operation=deploy;; mobile) operation=apk;; *) exit 1;; esac
[[ "$command" == status || "$command" =~ ^${operation}\ [0-9a-f]{40}\ [0-9a-f]{64}$ ]] || {
    echo 'Unsupported deployment command' >&2; exit 1;
}
container=cikscoffee-dery-app
[[ $(/usr/bin/docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$container") == cikscoffee-dery ]] || exit 1
exec /usr/bin/docker exec -i "$container" /usr/local/sbin/cikscoffee-ci-entry "$mode" "$command"
