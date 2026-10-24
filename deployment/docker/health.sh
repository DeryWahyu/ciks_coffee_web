#!/usr/bin/env bash
set -euo pipefail
curl --fail --silent --show-error --max-time 10 -H 'Host: cikscoffee.biz.id' -H 'X-Forwarded-Proto: https' http://127.0.0.1/up >/dev/null
status=$(supervisorctl status cikscoffee-queue cikscoffee-reverb cikscoffee-php cikscoffee-nginx cikscoffee-cron)
[[ $(grep -c ' RUNNING ' <<< "$status") == 5 ]]
