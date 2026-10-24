# CiksCoffee on the shared VPS

Production uses Docker Compose project `cikscoffee-dery` at
`/home/dery/cikscoffee` on `45.66.153.149`, SSH port `20193`.
PHP, Nginx, Supervisor, cron, MariaDB, and cloudflared all run inside this
project's containers. No PHP or database packages are installed on the host.

The project has three containers: `cikscoffee-dery-app`,
`cikscoffee-dery-db`, and `cikscoffee-dery-tunnel`. It creates its own
private database network, egress network, and database volume. It does not
join existing application networks or use their database containers.
CPU/memory limits and rotating Docker logs bound resource use.

Only `127.0.0.1:18088` is published for origin diagnostics. Host ports
80, 81, and 443 and the existing reverse proxy remain owned by their current
applications. The tunnel shares the app's network namespace, so existing
Cloudflare routes to `http://127.0.0.1:80` continue to work. Public URLs remain
`https://cikscoffee.biz.id` and `https://cikscoffee.biz.id/api`.

## Files

The host project directory contains:

- `compose.yml`: this Compose configuration.
- `runtime/`: the repository's `deployment/` directory, used as build context.
- `www/`: releases, shared `.env`/storage/downloads, and the active app symlink.
- `secrets/`: database passwords, client configuration, and tunnel token.
- `backups/`: daily/deployment SQL dumps and the latest storage backup.
- `migration/`: private migration evidence and source backups.

The container retains `/var/www/cikscoffee`, `/var/www/cikscoffee-releases`,
and `/var/www/cikscoffee-shared`. The application key, Firebase credentials,
uploaded files, APKs, and database contents survive container replacement.
The migrated `.env` changes `DB_HOST` to `db`; it keeps the original database
credentials, application key, and public domain.

## Operations

Use only this project's Compose file and project name:

```bash
cd /home/dery/cikscoffee
sudo docker compose -p cikscoffee-dery --profile production ps
sudo docker compose -p cikscoffee-dery logs --tail=100 app
sudo docker exec cikscoffee-dery-app supervisorctl status
sudo docker exec cikscoffee-dery-app /usr/local/bin/cikscoffee-backup
curl -fsS -H 'Host: cikscoffee.biz.id' http://127.0.0.1:18088/up
```

The tunnel starts only with `--profile production`, after the source server
has been frozen and the final database/files copied. There must be only one
active origin after cutover; starting both connectors against independent
databases would split writes. Leave the source server frozen for recovery.
After new writes, moving back requires transferring the newest data first.

To rebuild the runtime after reviewing a PHP/configuration change, update
`runtime/` from `deployment/` and run a build and replacement restricted to
this project. Recreate the tunnel when replacing the app container, because
it shares that container's network namespace. Never run daemon-wide prune,
restart, stop, or shared-stack commands for this application's maintenance.

## CI/CD

Web and mobile workflows keep the existing test/build/archive/signature
checks. GitHub secrets in both repositories point to the new server:
`VPS_HOST=45.66.153.149`, `VPS_PORT=20193`, `VPS_USER=dery`,
`VPS_SSH_KEY`, and pinned `VPS_KNOWN_HOSTS`. Each repository has its own
restricted key appended to dery's existing authorized keys. Mobile retains
the existing `ANDROID_DEBUG_KEYSTORE_B64` for compatible APK installation.

Install `install-ci.sh` once as administrator, passing the two public key
files. The forced SSH command invokes a root-owned wrapper which validates
the command and targets only the `cikscoffee-dery-app` container. The wrapper
does not accept arbitrary shell or Docker commands. Web keys can deploy
archives; mobile keys can only publish APKs. Password SSH is not used by CI.

Web deployment backs up production, enters maintenance, drains workers,
runs migrations and cache refresh, switches releases, restarts only PHP,
queue, and Reverb within this container, and checks health. Activation
failure restores the prior code; database migrations are not automatically
reversed. APK publication changes the latest alias atomically. Phones still
need to download and install new APKs manually.
