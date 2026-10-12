#!/usr/bin/env bash
set -euo pipefail
revision=${1:?Commit SHA required}
archive=${2:?Output archive path required}
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || { echo 'Invalid commit SHA' >&2; exit 1; }
test -s vendor/autoload.php
test -s public/build/manifest.json
printf '%s\n' "$revision" > .release-revision
tar -czf "$archive" \
  --exclude='./.git' --exclude='./.github' --exclude='./.env*' \
  --exclude='./node_modules' --exclude='./storage' --exclude='./bootstrap/cache' \
  --exclude='./public/storage' --exclude='./public/downloads' --exclude='./public/hot' \
  --exclude='./database/database.sqlite*' --exclude='./tests' --exclude='./.phpunit.cache' \
  --exclude='./auth.json' --exclude='./.idea' --exclude='./.vscode' --exclude='./.codex' \
  .
python3 deployment/ubuntu/validate-release.py "$archive" "$revision"
