# Support bundles

`convoy support` collects what the Convoy team needs to diagnose a broken install, removes secrets from it
on the operator's machine, shows the operator what it collected, and, if they agree, uploads it to the
console. The operator gets a short code to paste into Discord or a GitHub issue. Only the Convoy team can
open a bundle, and it is deleted after 30 days.

It replaces [ConvoyPanel/log-collector](https://github.com/ConvoyPanel/log-collector), which posted the same
kind of data to a public paste site and removed nothing from inside the logs.

This file is the contract between the CLI (`docker/convoy`) and the console. The console's parser points
here. A change either side can't read changes `schema`, and the console changes first.

## What is collected

Everything is optional except `manifest.json`. A command that fails, a container that isn't there, a file
that doesn't exist: the bundle leaves it out and says so in the manifest rather than failing.

- **Containers**: `docker compose ps --format json`, and each service's logs since `--since` (default 48
  hours), the last 5 MiB per service, with Docker's timestamps.
- **Log files**: `storage/logs/*.log` when the install has them (v4 writes `laravel.log`, `horizon.log` and
  `scheduler.log`; 5.x logs to stderr, so these are usually absent). Last 5 MiB each.
- **Upgrade**: the newest `convoy import-v4` log (pgloader output and the row-count comparison), if any.
- **Host**: OS release, kernel, architecture, CPU count, memory and swap, free disk for the install
  directory and for Docker's root, Docker and Compose versions.
- **Diagnostics**, run through the web image so they work even while the stack is down:
  `artisan about --json`, `horizon:status`, `migrate:status`, and the failed-job count with the last 20
  exception messages.
- **Settings**: `env.json`, below.

## What is never collected

- Secret values: anything in `.env` whose name ends in `_KEY`, `_SECRET`, `_PASSWORD` or `_TOKEN`, and
  `APP_KEY`. `env.json` says only whether each is set.
- Queued or failed job payloads. Only the exception message of a failed job is kept.
- Database contents, beyond what Laravel's own log lines and the diagnostics above print.
- The telemetry installation id. A bundle names the install (`APP_URL` is in it); tying it to the
  telemetry id would make the anonymous reports traceable.

### Redaction

Before anything is written to the archive, every collected file is passed through one redaction step
(bash and `sed -E`, tested by `tests/cli/redact.sh` against fixtures in CI). It replaces with
`[redacted]`:

- the exact value of every secret `.env` key listed above, wherever it appears;
- Proxmox API tokens: `PVEAPIToken=…` and `user@realm!tokenid=<uuid>`;
- `Authorization: Bearer …` and `Basic …` values;
- Laravel keys (`base64:` followed by 40 or more base64 characters);
- session and XSRF cookies (`convoy_session=…`, `XSRF-TOKEN=…`, `laravel_session=…`);
- the values of JSON fields named `password`, `secret`, `token` or `api_key`.

Redaction is a safety net, not a licence: the collector still never reads anything it doesn't need.

## Consent

The CLI writes the archive to `$CONVOY_DIR/backups/support/convoy-support-<UTC stamp>.tar.gz`, prints
every file in it with its size, and asks:

> Upload this bundle to the Convoy team's console? Only the Convoy team can see it, and it is deleted
> after 30 days. [y/N]

`--no-upload` stops before the question; `--yes` answers it. Nothing leaves the machine without one of
the two.

## The requests

```
POST https://convoypanel.com/api/v1/support-bundles
Content-Type: multipart/form-data   (one part, `bundle`: the .tar.gz)
Accept: application/json
User-Agent: Convoy/<panel version> convoy-cli/<collector version>
```

| Status | Body | Meaning |
|---|---|---|
| `201` | `{"code": "CVY-7K3M-Q2XP", "delete_token": "<40 characters>", "expires_at": "<ISO 8601>"}` | Stored. |
| `413` | | Larger than 20 MiB. |
| `422` | `{"message": "..."}` | Not a gzip tar, or `manifest.json` missing, unreadable or of an unknown `schema`. |
| `429` | | Too many uploads: 10 an hour from one address, 200 an hour in total. |

The CLI prints the code and keeps the response in `$CONVOY_DIR/backups/support/<code>.json` (mode 600).

```
DELETE https://convoypanel.com/api/v1/support-bundles/{code}
Authorization: Bearer <delete_token>
```

answers `204` whether or not the code exists, and erases the bundle and everything parsed from it.
`convoy support delete <code>` sends it.

Codes are `CVY-` and two groups of four characters from Crockford's base32 alphabet
(`0-9 A-H J K M N P-T V-Z`; no I, L, O or U), so they survive being read aloud or retyped. The console
compares them case-insensitively.

Connect timeout 10 seconds, whole request 120.

## The archive

A gzip-compressed tar. Regular files only, no links, no absolute paths, no `..`, at most 200 files and
200 MiB unpacked. The console rejects anything outside this layout:

```
manifest.json
env.json
logs/<service>.log
logs/storage/<name>.log
diagnostics/about.json
diagnostics/horizon-status.txt
diagnostics/migrate-status.txt
diagnostics/failed-jobs.json
upgrade/import-v4.log
```

`<service>` and `<name>` match `^[a-z0-9][a-z0-9_.-]{0,63}$`.

### `manifest.json`

```json
{
    "schema": 1,
    "collector": "5.0.0",
    "panel": "5.0.0",
    "layout": "5.x",
    "collected_at": "2026-11-13T05:00:00Z",
    "since": "48h",
    "host": {
        "os": "Debian GNU/Linux 13 (trixie)",
        "kernel": "6.12.48-amd64",
        "arch": "x86_64",
        "cpus": 4,
        "memory_mib": 7936,
        "swap_mib": 0,
        "disk_free_mib": { "install": 40210, "docker": 40210 },
        "docker": "28.5.1",
        "compose": "2.40.0"
    },
    "containers": [
        { "service": "web", "state": "running", "health": "healthy", "image": "ghcr.io/convoypanel/panel:5.0.0", "started_at": "2026-11-12T22:14:03Z" }
    ],
    "skipped": [
        { "item": "diagnostics/horizon-status.txt", "reason": "worker container not running" }
    ]
}
```

| Field | Meaning |
|---|---|
| `schema` | `1`. |
| `collector` | Version of the `convoy` script that made the bundle. |
| `panel` | `config('app.version')` if a diagnostic could read it, else the image tag or the v4 `config/app.php` value, else `null`. |
| `layout` | `5.x` (compose.yml with `web`, `worker`, `scheduler`, `postgres`, `redis`) or `v4` (docker-compose.yml with `php`, `workers`, `caddy`, `database`, `redis`). |
| `collected_at` | UTC, when collection started. |
| `since` | The window the container logs cover, as passed to `docker compose logs --since`. |
| `host` | As above. Any field may be `null` when it can't be read. No hostname or IP addresses. |
| `containers` | One entry per service in the compose project, running or not. `health` is `null` for a service with no healthcheck. |
| `skipped` | Each optional item that was left out, and why. |

### `env.json`

A flat object of the install's `.env`. Keys on this list carry their value:

`APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_DOMAIN`, `APP_TIMEZONE`, `APP_LOCALE`, `CONVOY_VERSION`,
`CONVOY_AUTO_HTTPS`, `CONVOY_HTTP_PORT`, `CONVOY_HTTPS_PORT`, `TRUSTED_PROXIES`, `DB_CONNECTION`,
`DB_HOST`, `DB_PORT`, `REDIS_HOST`, `REDIS_PORT`, `CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER`,
`SESSION_LIFETIME`, `LOG_CHANNEL`, `LOG_LEVEL`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`,
`MAIL_ENCRYPTION`, `CONVOY_TELEMETRY_ENABLED`, `CONVOY_ANNOUNCEMENTS_URL`.

Every other key is reported as `{"set": true}` or `{"set": false}` (present but empty), so the console can
tell "no APP_KEY" from "an APP_KEY we didn't send".

### Log lines

`logs/<service>.log` is `docker compose logs --timestamps --no-color --no-log-prefix`: each line starts
with Docker's RFC 3339 timestamp, then the container's own output. Laravel's entries inside it, and in
`logs/storage/*.log`, keep Laravel's format (`[2026-11-13 04:59:58] production.ERROR: message {context}`,
followed by `[stacktrace]` lines), which the console groups by exception.

### `diagnostics/failed-jobs.json`

```json
{ "count": 3, "recent": [{ "failed_at": "2026-11-12T21:00:04Z", "queue": "default", "job": "Convoy\\Jobs\\Server\\MonitorBackupJob", "exception": "Convoy\\Exceptions\\Repository\\Proxmox\\ProxmoxConnectionException: ..." }] }
```

`exception` is the first line of the stored exception only.

## Retention

The console deletes a bundle and everything parsed from it 30 days after upload, unless a Convoy admin
marks it as kept for an open case. The operator can delete it at any time with the delete token.
