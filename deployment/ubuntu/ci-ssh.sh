#!/usr/bin/env bash
set -euo pipefail
mode=${1:?Deployment mode required}
exec /usr/bin/sudo -n /usr/local/sbin/cikscoffee-ci-entry "$mode" "${SSH_ORIGINAL_COMMAND:-}"
