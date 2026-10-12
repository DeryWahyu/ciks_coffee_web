# CiksCoffee VPS deployment

Checked and installed on 2026-10-12. Host: `103.119.237.38`, external SSH port
`20253`, Ubuntu 24.04 LTS, 1 vCPU, 2 GB RAM, 20 GB virtual disk.

## Running services

- Application: `/var/www/cikscoffee`.
- Nginx: `127.0.0.1:80`, root `/var/www/cikscoffee/public`.
- PHP 8.3-FPM: two on-demand workers, 512 MB per-request limit.
- MariaDB: `127.0.0.1:3306`, 256 MB buffer pool, 30 connection limit.
- Supervisor: one queue worker and one Reverb server on `127.0.0.1:8080`.
- 1 GB swap, daily Laravel logs retained for seven days.
- Firewall allows internal SSH port 22. The provider forwards external 20253.
- All application services were verified to start after a VPS reboot.

The production database starts with the requested owner account
`pemilik@cikscoffee.com`. No demo employees, products, orders, or local database
were imported. Customer accounts log in through the mobile app; owner and
employee accounts use the web interface.

## Cloudflare routing

The domain has Cloudflare nameservers and proxied public DNS. The connector is
installed and enabled, with four authenticated Cloudflare connections and a
successful readiness check. Both published hostname routes were received by
the connector in configuration version 2. Public HTTPS works on the main
domain and www after the DNS update. Health checks return 200; the API returns
JSON 401 for unauthenticated requests. Public traffic is confirmed to reach the
tunnel. Nginx redirects HTTP visitors to HTTPS with status 308 and sends HSTS.

Tunnel ID: `31ceef66-16fc-43fa-820d-c87ca1b529a0`. Open this tunnel in Cloudflare
Networking > Tunnels to inspect its published application routes.

The server-side token is stored at `/etc/cloudflared/cikscoffee.token`, owned
by root:cloudflared, mode 0640, in a directory with mode 0750. The systemd unit
at `/etc/systemd/system/cloudflared.service` is enabled and running. The token
is excluded from all Git files and published artifacts.

These routes are configured with no path restriction:

| Hostname | Service URL |
| --- | --- |
| cikscoffee.biz.id | http://127.0.0.1:80 |
| www.cikscoffee.biz.id | http://127.0.0.1:80 |

The domain's DNS records should retain these CNAME targets, with Proxied
(orange cloud) and TTL Auto:

| Type | Name | Target |
| --- | --- | --- |
| CNAME | @ | 31ceef66-16fc-43fa-820d-c87ca1b529a0.cfargotunnel.com |
| CNAME | www | 31ceef66-16fc-43fa-820d-c87ca1b529a0.cfargotunnel.com |

Preserve mail and verification records. Public traffic goes through Cloudflare
HTTPS and an encrypted tunnel; the last hop
from cloudflared to Nginx stays on loopback.

WebSockets work through Cloudflare, and HTTPS redirects are enforced by Nginx.
Do not put a browser challenge or Cloudflare Access login in front of the mobile
API. Avoid caching
authenticated responses, `/api/*`, `/login`, `/logout`, `/pemilik/*`,
`/karyawan/*`, and `/broadcasting/auth`. The `/build/` files are static assets.

## Validation performed

- Backend suite in a separate `/opt/cikscoffee-validation` environment:
  60 tests, 288 assertions passed. The test database used in-memory SQLite.
- HTTP origin checks: owner login, 15 owner pages, CSV/XLSX/PDF export,
  customer registration/login/catalog/history/logout, and protected files.
- The same web and mobile API flows passed through public HTTPS, with a
  browser User-Agent for web requests and Dart/3.11 (dart:io) for mobile API
  requests. Production JS/CSS assets load, private responses are not cached,
  and cookies retain their Secure attributes.
- The empty floor-layout API correctly returns 404 until the owner creates a
  layout. Production smoke users were removed afterwards.
- Reverb: real WebSocket upgrade through Nginx, private-channel authorization,
  and delivery of a queued `mobile-order.created` event passed.
- The same private-channel and queued-event checks passed through public WSS
  via Cloudflare. Temporary users and their sessions were removed afterwards.
- Firebase service account OAuth authentication passed. Delivery to a physical
  customer's device still requires a device token and an end-to-end device test.
- Composer production audit and npm audit: no advisories after compatible
  dependency updates.
- Flutter analyze passed and all 10 Flutter tests passed, including migration
  away from saved local-server settings.

The Flutter API is fixed at `https://cikscoffee.biz.id/api`, and local server
controls are removed. All Android build variants require HTTPS. The compiled
APK contains the production URL and does not contain the previous LAN host.

The current APK retains the project's Android Debug signing certificate for
manual installation and compatibility with existing builds. A dedicated
release signing key is required before store distribution.

Manual APK download:
`https://cikscoffee.biz.id/downloads/CiksCoffee-1.0.0-production.apk`.
Its SHA-256 is
`ac47667733866eba2d5f39e0dc3baa8c6b38080da5710913f65f3d31dcdfcec1`.

## Maintenance

Inspect services:

```bash
systemctl status nginx php8.3-fpm mariadb supervisor cloudflared
supervisorctl status
journalctl -u cloudflared -n 50 --no-pager
tail -n 50 /var/log/supervisor/cikscoffee-queue.log
free -m
df -h /
```

After application/configuration updates:

```bash
cd /var/www/cikscoffee
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize
sudo -u www-data php artisan queue:restart
supervisorctl restart cikscoffee-reverb
systemctl reload php8.3-fpm
```

Do not regenerate the production APP_KEY. Runtime secrets and the Firebase
service-account file are excluded from the Git repository and app archives.

## Backups

`/usr/local/bin/cikscoffee-backup` creates a compressed database dump and an
archive of `.env` and `storage/app` under `/var/backups/cikscoffee` with private
permissions. A cron job runs daily at 19:30 UTC. Database snapshots are retained
for seven days; the storage archive holds the latest snapshot.

Off-server copies, including the verified `20261012T131344Z` snapshot, were
saved to `C:\Users\Lenovo\.codex\cikscoffee-backups`. Checksums match the server.
Scheduled off-server backup still
requires an external storage destination. These archives contain credentials
and must stay private.
