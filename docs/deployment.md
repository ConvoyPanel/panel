# Deployment

Convoy 5 is deployed as a container image. The supported install is a dedicated
host running Docker, with the panel, its queue worker, its scheduler and,
unless you bring your own, Postgres and Redis on it.

Convoy expects to be the only application on the host. It binds ports 80 and
443 and assumes it owns them.

## Install

```bash
curl -fsSL https://install.convoypanel.com/5 | sudo bash
```

The installer asks for the hostname customers will reach the panel on and an
email address for the first administrator, installs Docker if it is missing,
writes `/opt/convoy`, installs the `convoy` command, starts the stack, and
prints a URL and a temporary password.

Non-interactively:

```bash
curl -fsSL https://install.convoypanel.com/5 | sudo bash -s -- \
  --domain panel.example.com --email you@example.com --yes
```

`--version` picks what to install: `5` (the default) follows the newest 5.x
release, `5.1` the newest 5.1.x, and `5.0.1` exactly that release. The choice is
written to `CONVOY_VERSION` in `/opt/convoy/.env`, so `convoy upgrade` stays
within it and never moves a 5.x install to 6.0 on its own.

`5` and `5.1` only follow full releases. A pre-release such as
`5.0.0-alpha.1` is installed only when named exactly, and stays pinned until
`CONVOY_VERSION` is changed.

### Requirements

- Debian 12/13, Ubuntu 22.04/24.04, AlmaLinux 9/10 or Rocky Linux 9/10
- 2 GB RAM minimum, 4 GB recommended
- 20 GB disk
- Ports 80 and 443 free and reachable
- `x86_64` or `arm64` (upgrading from v4 needs `x86_64`; see below)

### TLS

If the hostname you give the installer is a domain that resolves to the host, a
certificate is obtained automatically over ACME and renewed without further
action. Certificates are stored in a volume, so they survive upgrades.

If you give it an IP address, the panel serves a self-signed certificate and
browsers will warn on first visit: certificate authorities do not issue for
bare IPs. Point a domain at the host and re-run with `--domain` to fix this.

To terminate TLS somewhere else instead, set `CONVOY_AUTO_HTTPS=off` and put your
proxy in front, then set `TRUSTED_PROXIES` in `/opt/convoy/.env` to its address.
Otherwise every client appears to Convoy as the proxy.

## Upgrading from v4

A v4 install (built from source in `/var/www/convoy`, on MySQL) moves onto
Convoy 5 with one command. Rehearse first: `--dry-run` converts a copy of your
data and checks it while v4 keeps serving.

```bash
curl -fsSL https://install.convoypanel.com/5 | sudo bash -s -- --import /var/www/convoy --dry-run
convoy import-v4 /var/www/convoy
```

The import keeps v4's `APP_KEY` (it decrypts node credentials and two-factor
secrets), its URL and its mail settings. It dumps v4's MySQL database to
`/opt/convoy/backups`, converts it to Postgres with pgloader, compares the row
count of every table, starts Convoy 5 and runs `artisan import:verify`.
`/var/www/convoy` and its MySQL data are only ever read, so v4 can always be
brought back; the import prints the exact commands when it finishes or fails.

It needs v4.6.2 or later, an `x86_64` host (pgloader crashes under arm64
emulation), and v4's queue empty: it waits up to five minutes for running jobs,
such as a backup, to finish.

## Day-to-day

`convoy` wraps the underlying `docker compose` commands:

```
convoy ps                    status of every container
convoy logs web              follow the panel's logs
convoy upgrade               back up, pull new images, restart, verify
convoy backup                dump the database to /opt/convoy/backups
convoy artisan <cmd>         run an Artisan command
convoy shell                 open a shell in the panel container
convoy horizon               queue worker status
convoy export-activity       export the activity log as CSV
convoy support               send logs to the Convoy team (see below)
```

Nothing here is magic. If you know Docker, `cd /opt/convoy` and use
`docker compose` directly.

## Upgrading

```bash
convoy upgrade
```

This takes a database backup, pulls the new images, restarts the stack, and
waits for the panel to report healthy. Migrations run automatically when the web
container starts.

## What is actually running

| Service | What it does |
| --- | --- |
| `web` | Serves the panel. FrankenPHP (Caddy + PHP in one process), which also terminates TLS. Its healthcheck, `/up`, fails when Postgres or Redis is unreachable. |
| `worker` | Horizon. Every Proxmox action is a queued job, so nothing works without this. |
| `scheduler` | `schedule:work`. Runs the timed jobs in `app/Console/Kernel.php`, such as the usage and rate-limit syncs. |
| `postgres` | Bundled database. |
| `redis` | Bundled Redis, used for the queue, cache and sessions. |

The first three are the same image with different commands. All application
configuration lives in `/opt/convoy/.env` and is read by all three.

## Using your own database and Redis

Point `DB_*` and `REDIS_*` at your own hosts in `/opt/convoy/.env`, then edit
`/opt/convoy/compose.yml` and delete:

- the `postgres` and `redis` services,
- the `depends_on:` block from `web`, `worker` and `scheduler`,
- the `postgres:` and `redis:` entries under `volumes:`.

Then apply it:

```bash
cd /opt/convoy
docker compose up -d --remove-orphans
```

The image is identical either way. Your edits to `compose.yml` survive upgrades,
which only pull new images, but they are yours to re-apply if a future release
changes the file.

`convoy backup` only handles the bundled database; with an external one, back
it up with your provider's tooling.

## Backups

`convoy backup` writes a gzipped `pg_dump` to `/opt/convoy/backups`. It is run
automatically before every upgrade. It is *not* run on a schedule; put it in
cron if you want that:

```
0 3 * * * /usr/local/bin/convoy backup >/dev/null
```

Volumes hold the database, Redis data, issued certificates and the contents of
`storage/`. `docker compose down` leaves all of them in place; `down -v` destroys
them.

## Getting help: `convoy support`

`convoy support` collects the container logs, a few diagnostics and details
about the host into `/opt/convoy/backups/support`, removes secrets from them,
shows you what it collected, and asks who may open it before uploading it to
the Convoy team. You get a link to paste in Discord or a GitHub issue.

```bash
convoy support
```

It needs Convoy 5, so a v4 install upgrades first. If the upgrade itself fails,
run `convoy support` all the same: the installer has already set up
`/opt/convoy` and the command, and the upload includes the import's log.

What it collects and removes is documented in
[support-bundle.md](support-bundle.md). `--no-upload` writes the archive and
stops, so you can read it first.

## Troubleshooting

**The panel container will not start.** Its startup checks fail loudly and on
purpose. `convoy logs web` will name the problem: a missing `APP_KEY`, an
`APP_URL` still pointing at localhost, or a storage volume the container cannot
write to.

**Everything looks fine but nothing happens when I act on a server.** The worker
is down. `convoy horizon` tells you; `convoy logs worker` tells you why.

**Certificate errors.** ACME needs port 80 reachable from the internet and a
domain that resolves to this host. `convoy logs web` includes Caddy's output,
which says precisely which of those failed.
