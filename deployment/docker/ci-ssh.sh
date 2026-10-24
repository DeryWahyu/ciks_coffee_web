#!/usr/bin/env bash
set -euo pipefail
exec /usr/bin/sudo -n /usr/local/sbin/cikscoffee-dery-ci "${1:?Mode required}" "${SSH_ORIGINAL_COMMAND:-}"
